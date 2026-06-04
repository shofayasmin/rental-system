<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Contract;
use App\Models\ContractExtension;
use App\Models\Property;
use App\Models\PropertyAvailabilityCycle;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;


class ContractController extends Controller
{
    private function redirectContractsHomeForUser()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect('/login');
        }

        return match ($user->role) {
            'tenant' => redirect('/tenant/contracts'),
            'agent' => redirect('/agent/rental-requests'),
            default => redirect('/'),
        };
    }

    private function canUserAccessTransaction(Transaction $transaction): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        if ((int) $transaction->tenant_id === (int) $user->id) {
            return true;
        }

        // Allow the agent who owns the property/transaction to view the contract detail.
        if ($user->role === 'agent' && (int) $transaction->agent_id === (int) $user->id) {
            return true;
        }

        return false;
    }

    public function show(Transaction $transaction)
    {
        if (!$this->canUserAccessTransaction($transaction)) {
            abort(403);
        }

        if ($transaction->status !== 'paid') {
            return $this->redirectContractsHomeForUser()
                ->with('error', 'This contract can be opened after payment is completed.');
        }

        return view('contracts.show', $this->buildContractViewData($transaction));
    }

    public function download(Transaction $transaction)
    {
        if (!$this->canUserAccessTransaction($transaction)) {
            abort(403);
        }

        if ($transaction->status !== 'paid') {
            return $this->redirectContractsHomeForUser()
                ->with('error', 'This contract PDF can be downloaded after payment is completed.');
        }

        $data = $this->buildContractViewData($transaction);
        $fileName = 'contract-' . $transaction->id . '.pdf';

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'Times-Roman');

        $dompdf = new Dompdf($options);
        $html = view('contracts.pdf', $data)->render();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    public function extend(Request $request, Contract $contract)
    {
        if ($contract->rentalRequest->tenant_id !== Auth::id()) {
            abort(403);
        }

        if ($contract->status !== 'active') {
            return back()->with('error', 'Only an active contract can be extended.');
        }

        if ($contract->rentalRequest->status !== 'paid') {
            return back()->with('error', 'Initial rent must be paid before requesting an extension.');
        }

        $validated = $request->validate([
            'months_requested' => 'nullable|integer|min:1|max:12',
        ]);

        $monthsRequested = (int) ($validated['months_requested'] ?? 1);
        $monthlyRent = (float) ($contract->monthly_rent ?? $contract->total_price);

        $hasOpenExtensionRequest = $contract->extensions()
            ->whereIn('status', ['pending', 'awaiting_payment'])
            ->exists();

        if ($hasOpenExtensionRequest) {
            return back()->with('error', 'There is already an extension request in progress for this contract.');
        }

        $oldEndDate = Carbon::parse($contract->end_date)->toDateString();
        $newEndDate = Carbon::parse($contract->end_date)->addMonths($monthsRequested)->toDateString();
        $amount = $monthlyRent * $monthsRequested;

        ContractExtension::create([
            'contract_id' => $contract->id,
            'months_requested' => $monthsRequested,
            'monthly_rent_snapshot' => $monthlyRent,
            'amount' => $amount,
            'status' => 'pending',
            'old_end_date' => $oldEndDate,
            'new_end_date' => $newEndDate,
            'extended_at' => now(),
        ]);

        return back()->with('success', 'Extension request submitted. Waiting for agent approval.');
    }

    public function approveExtension(ContractExtension $extension)
    {
        $extension->loadMissing('contract.rentalRequest.property');
        $contract = $extension->contract;
        $rentalRequest = $contract->rentalRequest;
        $property = $rentalRequest->property;

        if ($property->agent_id !== Auth::id()) {
            abort(403);
        }

        if ($extension->status !== 'pending') {
            return back()->with('error', 'This extension request is no longer pending.');
        }

        if ($contract->status !== 'active') {
            return back()->with('error', 'Cannot approve extension for a non-active contract.');
        }

        DB::transaction(function () use ($extension, $contract, $rentalRequest, $property) {
            $amount = (float) ($extension->amount ?? 0);
            if ($amount <= 0) {
                $amount = (float) ($extension->monthly_rent_snapshot ?? $contract->monthly_rent ?? $contract->total_price) * (int) $extension->months_requested;
            }

            $approvedAt = now();

            Transaction::create([
                'rental_request_id' => $rentalRequest->id,
                'property_id' => $property->id,
                'tenant_id' => $rentalRequest->tenant_id,
                'agent_id' => $property->agent_id,
                'contract_extension_id' => $extension->id,
                'amount' => $amount,
                'type' => 'extension_rent',
                'status' => 'unpaid',
            ]);

            $extension->update([
                'amount' => $amount,
                'status' => 'awaiting_payment',
                'approved_by' => Auth::id(),
                'approved_at' => $approvedAt,
                'payment_due_at' => $approvedAt->copy()->addDays(7),
            ]);
        });

        return back()->with('success', 'Extension approved. Payment request created for tenant.');
    }

    public function rejectExtension(ContractExtension $extension)
    {
        $extension->loadMissing('contract.rentalRequest.property');
        $property = $extension->contract->rentalRequest->property;

        if ($property->agent_id !== Auth::id()) {
            abort(403);
        }

        if ($extension->status !== 'pending') {
            return back()->with('error', 'Only pending extension requests can be rejected.');
        }

        $extension->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'payment_due_at' => null,
        ]);

        return back()->with('success', 'Extension request rejected.');
    }

    public function cancelExtensionByTenant(ContractExtension $extension)
    {
        $extension->loadMissing('contract.rentalRequest');
        $rentalRequest = $extension->contract->rentalRequest;

        if ((int) $rentalRequest->tenant_id !== (int) Auth::id()) {
            abort(403);
        }

        if (!in_array($extension->status, ['pending', 'awaiting_payment'], true)) {
            return back()->with('error', 'Only pending or awaiting-payment extension requests can be cancelled.');
        }

        $cancelled = false;

        DB::transaction(function () use ($extension, &$cancelled) {
            $lockedExtension = ContractExtension::query()
                ->whereKey($extension->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedExtension || !in_array($lockedExtension->status, ['pending', 'awaiting_payment'], true)) {
                return;
            }

            $lockedExtension->update([
                'status' => 'cancelled_by_tenant',
                'cancelled_at' => now(),
                'rejected_at' => null,
                'payment_due_at' => null,
            ]);

            Transaction::query()
                ->where('contract_extension_id', $lockedExtension->id)
                ->where('type', 'extension_rent')
                ->where('status', 'unpaid')
                ->update(['status' => 'failed']);

            $cancelled = true;
        });

        if (!$cancelled) {
            return back()->with('error', 'This extension request can no longer be cancelled.');
        }

        return back()->with('success', 'Extension request cancelled.');
    }

    public function endRental(Request $request, Contract $contract)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'agent') {
            abort(403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|min:10|max:1000',
        ]);

        $contract->loadMissing('rentalRequest.property');
        $property = data_get($contract, 'rentalRequest.property');

        if (!$property) {
            return back()->with('error', 'Contract does not have a valid property reference.');
        }

        if ($property->agent_id !== $user->id) {
            abort(403);
        }

        if ($contract->status !== 'active') {
            return back()->with('error', 'Only active contracts can be ended manually.');
        }

        DB::transaction(function () use ($contract, $validated, $user) {
            $lockedContract = Contract::query()
                ->whereKey($contract->id)
                ->with('rentalRequest')
                ->lockForUpdate()
                ->first();

            if (!$lockedContract || $lockedContract->status !== 'active') {
                return;
            }

            $propertyId = (int) data_get($lockedContract, 'rentalRequest.property_id');
            $property = Property::query()
                ->whereKey($propertyId)
                ->lockForUpdate()
                ->first();

            if (!$property) {
                return;
            }

            $endedAt = now();
            $lockedContract->update([
                'status' => 'ended',
                'end_date' => $endedAt->toDateString(),
                'ended_reason' => $validated['reason'],
                'ended_by' => $user->id,
                'ended_at' => $endedAt,
            ]);

            $hasOtherActiveContract = Contract::query()
                ->where('id', '!=', $lockedContract->id)
                ->where('status', 'active')
                ->whereHas('rentalRequest', function ($query) use ($propertyId) {
                    $query->where('property_id', $propertyId);
                })
                ->lockForUpdate()
                ->exists();

            if ($hasOtherActiveContract) {
                return;
            }

            if ($property->status === 'rented') {
                $property->update(['status' => 'to-let']);
            }

            $this->openAvailabilityCycleIfNeeded($property->id);
        });

        return back()->with('success', 'Rental ended successfully. Property is available for new tenants.');
    }

    private function openAvailabilityCycleIfNeeded(int $propertyId): void
    {
        $activeCycleExists = PropertyAvailabilityCycle::query()
            ->where('property_id', $propertyId)
            ->whereNull('unavailable_at')
            ->lockForUpdate()
            ->exists();

        if ($activeCycleExists) {
            return;
        }

        PropertyAvailabilityCycle::create([
            'property_id' => $propertyId,
            'available_from_at' => now(),
            'unavailable_at' => null,
            'closed_by' => null,
        ]);
    }

    private function buildContractViewData(Transaction $transaction): array
    {
        $transaction->loadMissing([
            'tenant',
            'property.agent',
            'property.province',
            'property.regency',
            'property.district',
            'property.village',
            'rentalRequest.contracts',
            'contractExtension',
        ]);

        $contract = $transaction->rentalRequest->contracts
            ->sortBy('id')
            ->first();
        $isExtension = $transaction->type === 'extension_rent' && $transaction->contractExtension;
        $extension = $isExtension ? $transaction->contractExtension : null;

        $periodLabel = 'Rent Period';
        $periodMonths = 1;
        $periodAmount = (float) $transaction->amount;
        $periodStart = $transaction->updated_at->toDateString();
        $periodEnd = Carbon::parse($periodStart)->addMonth()->toDateString();
        $monthlyRent = $periodAmount;

        if ($isExtension) {
            $periodMonths = max(1, (int) ($extension->months_requested ?? 1));
            $periodStart = $extension->old_end_date ?: $transaction->updated_at->toDateString();
            $periodEnd = $extension->new_end_date ?: Carbon::parse($periodStart)->addMonths($periodMonths)->toDateString();
            $monthlyRent = (float) ($extension->monthly_rent_snapshot ?: ($periodAmount / $periodMonths));
        } elseif ($contract && $contract->start_date) {
            $periodStart = Carbon::parse($contract->start_date)->toDateString();
            $periodEnd = Carbon::parse($contract->start_date)->addMonth()->toDateString();
            $monthlyRent = (float) ($contract->monthly_rent ?? $periodAmount);
        }

        $contractSequence = Transaction::query()
            ->where('tenant_id', $transaction->tenant_id)
            ->where('property_id', $transaction->property_id)
            ->whereIn('type', ['initial_rent', 'extension_rent'])
            ->where('status', 'paid')
            ->where(function ($query) use ($transaction) {
                $query->where('updated_at', '<', $transaction->updated_at)
                    ->orWhere(function ($sameTimeQuery) use ($transaction) {
                        $sameTimeQuery->where('updated_at', '=', $transaction->updated_at)
                            ->where('id', '<=', $transaction->id);
                    });
            })
            ->count();

        return [
            'transaction' => $transaction,
            'tenant' => $transaction->tenant,
            'property' => $transaction->property,
            'agent' => $transaction->property->agent,
            'date' => $transaction->updated_at->toDateString(),
            'contract' => $contract,
            'extension' => $extension,
            'isExtension' => $isExtension,
            'monthlyRent' => $monthlyRent,
            'periodLabel' => $periodLabel,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'periodMonths' => $periodMonths,
            'periodAmount' => $periodAmount,
            'contractSequence' => max(1, $contractSequence),
        ];
    }

}
