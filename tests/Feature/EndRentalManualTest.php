<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Property;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndRentalManualTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_must_provide_early_checkout_reason(): void
    {
        [$agent, $contract] = $this->createAgentAndActiveContract();

        $response = $this->actingAs($agent)
            ->from('/agent/properties/' . $contract->rentalRequest->property_id)
            ->post('/agent/contracts/' . $contract->id . '/end-rental', [
                'reason' => '',
            ]);

        $response->assertSessionHasErrors(['reason']);
    }

    public function test_agent_can_end_rental_with_reason_and_property_returns_to_to_let(): void
    {
        [$agent, $contract] = $this->createAgentAndActiveContract();

        $response = $this->actingAs($agent)
            ->post('/agent/contracts/' . $contract->id . '/end-rental', [
                'reason' => 'Tenant checked out early due to relocation to another city.',
            ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'status' => 'ended',
            'ended_by' => $agent->id,
        ]);

        $this->assertDatabaseHas('properties', [
            'id' => $contract->rentalRequest->property_id,
            'status' => 'to-let',
        ]);

        $this->assertDatabaseHas('property_availability_cycles', [
            'property_id' => $contract->rentalRequest->property_id,
            'unavailable_at' => null,
            'closed_by' => null,
        ]);
    }

    private function createAgentAndActiveContract(): array
    {
        $agent = User::factory()->create([
            'role' => 'agent',
            'enabled' => true,
        ]);

        $tenant = User::factory()->create([
            'role' => 'tenant',
            'enabled' => true,
        ]);

        $property = Property::create([
            'agent_id' => $agent->id,
            'title' => 'Manual End Rental House',
            'address' => 'Jl. End Rental 123',
            'bedrooms' => 2,
            'bathrooms' => 1.0,
            'area' => 50,
            'building_area' => 45,
            'rent_price' => 3500000,
            'status' => 'rented',
        ]);

        $rentalRequest = RentalRequest::create([
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'status' => 'paid',
            'paid_at' => now()->subWeeks(2),
            'message' => 'Paid request for manual end rental test.',
        ]);

        $contract = Contract::create([
            'rental_request_id' => $rentalRequest->id,
            'start_date' => now()->subWeeks(2)->toDateString(),
            'end_date' => now()->addWeeks(2)->toDateString(),
            'monthly_rent' => 3500000,
            'total_price' => 3500000,
            'status' => 'active',
        ]);

        return [$agent, $contract];
    }
}
