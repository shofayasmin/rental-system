<?php

namespace App\Console\Commands;

use App\Models\ContractExtension;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireExtensionPaymentDue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'extensions:expire-payment-due';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire extension payments that passed 7-day payment due date';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expiredCount = 0;

        ContractExtension::query()
            ->select('id')
            ->where('status', 'awaiting_payment')
            ->whereNotNull('payment_due_at')
            ->where('payment_due_at', '<', now())
            ->orderBy('id')
            ->chunkById(100, function ($extensions) use (&$expiredCount) {
                foreach ($extensions as $extensionSummary) {
                    DB::transaction(function () use ($extensionSummary, &$expiredCount) {
                        $extension = ContractExtension::query()
                            ->whereKey($extensionSummary->id)
                            ->lockForUpdate()
                            ->first();

                        if (!$extension) {
                            return;
                        }

                        if (
                            $extension->status !== 'awaiting_payment' ||
                            !$extension->payment_due_at ||
                            now()->lte($extension->payment_due_at)
                        ) {
                            return;
                        }

                        $extension->update([
                            'status' => 'expired',
                        ]);

                        DB::table('transactions')
                            ->where('contract_extension_id', $extension->id)
                            ->where('type', 'extension_rent')
                            ->where('status', 'unpaid')
                            ->update(['status' => 'failed']);

                        $expiredCount++;
                    });
                }
            });

        $this->info("Expired {$expiredCount} extension payment(s).");

        return self::SUCCESS;
    }
}
