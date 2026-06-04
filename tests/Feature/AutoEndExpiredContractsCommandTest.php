<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Property;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoEndExpiredContractsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_ends_expired_contract_and_releases_property_to_to_let(): void
    {
        [$property, $rentalRequest] = $this->createRentedPropertyWithRequest();

        $contract = Contract::create([
            'rental_request_id' => $rentalRequest->id,
            'start_date' => now()->subMonths(2)->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
            'monthly_rent' => 3500000,
            'total_price' => 7000000,
            'status' => 'active',
        ]);

        $this->artisan('contracts:auto-end-expired')
            ->assertExitCode(0);

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'status' => 'ended',
        ]);

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => 'to-let',
        ]);

        $this->assertDatabaseHas('property_availability_cycles', [
            'property_id' => $property->id,
            'unavailable_at' => null,
            'closed_by' => null,
        ]);
    }

    public function test_it_keeps_property_rented_when_another_active_contract_exists(): void
    {
        [$property, $firstRequest] = $this->createRentedPropertyWithRequest();
        [, $secondRequest] = $this->createRentedPropertyWithRequest($property);

        Contract::create([
            'rental_request_id' => $firstRequest->id,
            'start_date' => now()->subMonths(2)->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
            'monthly_rent' => 3500000,
            'total_price' => 7000000,
            'status' => 'active',
        ]);

        Contract::create([
            'rental_request_id' => $secondRequest->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'monthly_rent' => 3500000,
            'total_price' => 3500000,
            'status' => 'active',
        ]);

        $this->artisan('contracts:auto-end-expired')
            ->assertExitCode(0);

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => 'rented',
        ]);
    }

    private function createRentedPropertyWithRequest(?Property $property = null): array
    {
        $tenant = User::factory()->create([
            'role' => 'tenant',
            'enabled' => true,
        ]);

        if (!$property) {
            $agent = User::factory()->create([
                'role' => 'agent',
                'enabled' => true,
            ]);

            $property = Property::create([
                'agent_id' => $agent->id,
                'title' => 'Auto End Contract House',
                'address' => 'Jl. Auto End 1',
                'bedrooms' => 2,
                'bathrooms' => 1.0,
                'area' => 50,
                'building_area' => 45,
                'rent_price' => 3500000,
                'status' => 'rented',
            ]);
        }

        $request = RentalRequest::create([
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'status' => 'paid',
            'paid_at' => now()->subMonth(),
            'message' => 'Paid request for contract lifecycle test.',
        ]);

        return [$property, $request];
    }
}
