<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\Property;
use App\Models\PropertyAvailabilityCycle;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoEndExpiredContracts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contracts:auto-end-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'End expired active contracts and release rented properties back to to-let.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $endedContracts = 0;
        $releasedProperties = 0;

        Contract::query()
            ->select('id')
            ->where('status', 'active')
            ->whereDate('end_date', '<', today())
            ->orderBy('id')
            ->chunkById(100, function ($contracts) use (&$endedContracts, &$releasedProperties) {
                foreach ($contracts as $contractSummary) {
                    DB::transaction(function () use ($contractSummary, &$endedContracts, &$releasedProperties) {
                        $contract = Contract::query()
                            ->whereKey($contractSummary->id)
                            ->with('rentalRequest')
                            ->lockForUpdate()
                            ->first();

                        if (!$contract || $contract->status !== 'active') {
                            return;
                        }

                        $contractEndDate = Carbon::parse($contract->end_date)->toDateString();
                        if ($contractEndDate >= today()->toDateString()) {
                            return;
                        }

                        $contract->update([
                            'status' => 'ended',
                            'ended_reason' => 'Auto-ended by scheduler because contract end date has passed.',
                            'ended_at' => now(),
                        ]);
                        $endedContracts++;

                        $propertyId = (int) data_get($contract, 'rentalRequest.property_id');
                        if ($propertyId <= 0) {
                            return;
                        }

                        $property = Property::query()
                            ->whereKey($propertyId)
                            ->lockForUpdate()
                            ->first();

                        if (!$property || $property->status !== 'rented') {
                            return;
                        }

                        $hasOtherActiveContract = Contract::query()
                            ->where('id', '!=', $contract->id)
                            ->where('status', 'active')
                            ->whereHas('rentalRequest', function ($query) use ($propertyId) {
                                $query->where('property_id', $propertyId);
                            })
                            ->lockForUpdate()
                            ->exists();

                        if ($hasOtherActiveContract) {
                            return;
                        }

                        $property->update(['status' => 'to-let']);
                        $this->openAvailabilityCycleIfNeeded($propertyId);
                        $releasedProperties++;
                    });
                }
            });

        $this->info("Ended {$endedContracts} contract(s); released {$releasedProperties} propert(y/ies).");

        return self::SUCCESS;
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
}
