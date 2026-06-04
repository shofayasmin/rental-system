<?php

namespace App\Console\Commands;

use App\Models\RentalRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpirePaymentDueRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'requests:expire-payment-due';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire rental requests that passed 7-day payment due date';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expiredCount = 0;

        RentalRequest::query()
            ->select('id')
            ->where('status', 'awaiting_payment')
            ->whereNotNull('payment_due_at')
            ->where('payment_due_at', '<', now())
            ->orderBy('id')
            ->chunkById(100, function ($requests) use (&$expiredCount) {
                foreach ($requests as $requestSummary) {
                    DB::transaction(function () use ($requestSummary, &$expiredCount) {
                        $request = RentalRequest::query()
                            ->whereKey($requestSummary->id)
                            ->lockForUpdate()
                            ->first();

                        if (!$request) {
                            return;
                        }

                        if (
                            $request->status !== 'awaiting_payment' ||
                            !$request->payment_due_at ||
                            now()->lte($request->payment_due_at)
                        ) {
                            return;
                        }

                        $request->update([
                            'status' => 'cancelled_lost',
                            'cancelled_at' => now(),
                        ]);

                        DB::table('transactions')
                            ->where('rental_request_id', $request->id)
                            ->where('type', 'initial_rent')
                            ->where('status', 'unpaid')
                            ->update(['status' => 'failed']);

                        $expiredCount++;
                    });
                }
            });

        $this->info("Expired {$expiredCount} request(s).");

        return self::SUCCESS;
    }
}
