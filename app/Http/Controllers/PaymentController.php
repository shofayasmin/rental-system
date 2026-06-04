<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Property;
use App\Models\PropertyAvailabilityCycle;
use App\Models\RentalRequest;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class PaymentController extends Controller
{
    public function pay(Transaction $transaction)
    {
        if ($transaction->tenant_id !== Auth::id()) {
            abort(403);
        }

        if ($transaction->status !== 'unpaid') {
            return back()->with('error', 'Transaction already processed.');
        }

        $paymentType = null;
        $expiredDue = false;
        $successMessage = 'Payment failed. This request is no longer eligible for payment.';

        DB::transaction(function () use ($transaction, &$paymentType, &$successMessage, &$expiredDue) {
            $lockedTransaction = Transaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTransaction->status !== 'unpaid') {
                return;
            }

            $paymentType = $lockedTransaction->type ?? 'initial_rent';

            if ($paymentType === 'initial_rent') {
                $rentalRequest = RentalRequest::with('property')
                    ->whereKey($lockedTransaction->rental_request_id)
                    ->lockForUpdate()
                    ->first();

                if (!$rentalRequest || $rentalRequest->status !== 'awaiting_payment') {
                    return;
                }

                if ($rentalRequest->payment_due_at && now()->gt($rentalRequest->payment_due_at)) {
                    $expiredDue = true;
                    return;
                }

                $property = Property::whereKey($rentalRequest->property_id)
                    ->lockForUpdate()
                    ->first();

                if (!$property) {
                    return;
                }

                $hasOtherWinner = RentalRequest::where('property_id', $property->id)
                    ->where('id', '!=', $rentalRequest->id)
                    ->where('status', 'paid')
                    ->lockForUpdate()
                    ->exists();

                if ($hasOtherWinner) {
                    return;
                }

                $paidAt = now();

                $lockedTransaction->update(['status' => 'paid']);

                Contract::firstOrCreate(
                    ['rental_request_id' => $rentalRequest->id],
                    [
                        'start_date' => $paidAt->toDateString(),
                        'end_date' => $paidAt->copy()->addMonth()->toDateString(),
                        'monthly_rent' => $lockedTransaction->amount,
                        'total_price' => $lockedTransaction->amount,
                        'status' => 'active',
                    ]
                );

                $rentalRequest->update([
                    'status' => 'paid',
                    'paid_at' => $paidAt,
                ]);

                $this->closeActiveAvailabilityCycle($property->id, $paidAt);

                $property->update(['status' => 'rented']);

                $loserIds = RentalRequest::where('property_id', $property->id)
                    ->where('id', '!=', $rentalRequest->id)
                    ->whereIn('status', ['pending_review', 'awaiting_payment'])
                    ->lockForUpdate()
                    ->pluck('id');

                if ($loserIds->isNotEmpty()) {
                    RentalRequest::whereIn('id', $loserIds)->update([
                        'status' => 'cancelled_lost',
                        'cancelled_at' => now(),
                    ]);

                    Transaction::whereIn('rental_request_id', $loserIds)
                        ->where('type', 'initial_rent')
                        ->where('status', 'unpaid')
                        ->update(['status' => 'failed']);
                }

                $successMessage = 'Payment successful. Property has been secured for you.';
                return;
            }

            if ($paymentType !== 'extension_rent' || !$lockedTransaction->contract_extension_id) {
                return;
            }

            $extension = $lockedTransaction->contractExtension()
                ->with('contract')
                ->lockForUpdate()
                ->first();

            if (!$extension || $extension->status !== 'awaiting_payment') {
                return;
            }

            $contract = $extension->contract;
            if (!$contract || $contract->status !== 'active') {
                return;
            }

            if ($extension->payment_due_at && now()->gt($extension->payment_due_at)) {
                $extension->update([
                    'status' => 'expired',
                ]);

                $lockedTransaction->update(['status' => 'failed']);
                $expiredDue = true;
                return;
            }

            $lockedTransaction->update(['status' => 'paid']);

            $oldEndDate = Carbon::parse($contract->end_date)->toDateString();
            $newEndDate = Carbon::parse($contract->end_date)
                ->addMonths((int) $extension->months_requested)
                ->toDateString();

            $contract->update([
                'end_date' => $newEndDate,
                'total_price' => (float) $contract->total_price + (float) $lockedTransaction->amount,
            ]);

            $extension->update([
                'status' => 'paid',
                'old_end_date' => $oldEndDate,
                'new_end_date' => $newEndDate,
                'paid_at' => now(),
                'extended_at' => now(),
            ]);

            $successMessage = 'Extension payment successful. Contract period updated.';
        });

        $transaction->refresh();
        if ($transaction->status !== 'paid') {
            if ($expiredDue) {
                return back()->with('error', 'Payment window expired. Please contact the agent for next steps.');
            }

            return back()->with('error', 'Payment failed. This request is no longer eligible for payment.');
        }

        return back()->with('success', $successMessage);
    }

    private function closeActiveAvailabilityCycle(int $propertyId, Carbon $paidAt): void
    {
        $activeCycle = PropertyAvailabilityCycle::query()
            ->where('property_id', $propertyId)
            ->whereNull('unavailable_at')
            ->orderByDesc('available_from_at')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        if (!$activeCycle) {
            return;
        }

        $activeCycle->update([
            'unavailable_at' => $paidAt,
            'closed_by' => 'rented',
        ]);
    }
}
