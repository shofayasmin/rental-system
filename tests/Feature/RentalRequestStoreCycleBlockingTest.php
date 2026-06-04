<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\PropertyAvailabilityCycle;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalRequestStoreCycleBlockingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_allows_new_application_when_previous_cancelled_request_is_from_old_cycle(): void
    {
        [$tenant, $property] = $this->createTenantAndProperty();

        $oldCycle = PropertyAvailabilityCycle::create([
            'property_id' => $property->id,
            'available_from_at' => now()->subDays(10),
            'unavailable_at' => now()->subDays(1),
            'closed_by' => 'manual',
        ]);

        $activeCycle = PropertyAvailabilityCycle::create([
            'property_id' => $property->id,
            'available_from_at' => now(),
            'unavailable_at' => null,
            'closed_by' => null,
        ]);

        RentalRequest::create([
            'property_id' => $property->id,
            'availability_cycle_id' => $oldCycle->id,
            'tenant_id' => $tenant->id,
            'status' => 'cancelled_by_tenant',
            'cancelled_at' => now()->subDay(),
            'message' => 'Cancelled previous request.',
        ]);

        $response = $this->actingAs($tenant)
            ->from('/houses/' . $property->id)
            ->post('/houses/' . $property->id . '/apply', [
                'message' => 'Applying in new cycle.',
            ]);

        $response->assertSessionHas('success', 'Rental request submitted');

        $this->assertDatabaseHas('rental_requests', [
            'property_id' => $property->id,
            'availability_cycle_id' => $activeCycle->id,
            'tenant_id' => $tenant->id,
            'status' => 'pending_review',
            'message' => 'Applying in new cycle.',
        ]);
    }

    public function test_it_blocks_new_application_when_paid_request_exists_in_active_cycle(): void
    {
        [$tenant, $property] = $this->createTenantAndProperty();

        $activeCycle = PropertyAvailabilityCycle::create([
            'property_id' => $property->id,
            'available_from_at' => now(),
            'unavailable_at' => null,
            'closed_by' => null,
        ]);

        RentalRequest::create([
            'property_id' => $property->id,
            'availability_cycle_id' => $activeCycle->id,
            'tenant_id' => $tenant->id,
            'status' => 'paid',
            'paid_at' => now()->subMinute(),
            'message' => 'Already paid in this cycle.',
        ]);

        $response = $this->actingAs($tenant)
            ->from('/houses/' . $property->id)
            ->post('/houses/' . $property->id . '/apply', [
                'message' => 'Second attempt.',
            ]);

        $response->assertSessionHas('warning', 'You already applied for this property.');

        $this->assertEquals(1, RentalRequest::query()
            ->where('property_id', $property->id)
            ->where('tenant_id', $tenant->id)
            ->count());
    }

    public function test_it_allows_new_application_when_previous_agent_cancelled_request_is_from_old_cycle(): void
    {
        [$tenant, $property] = $this->createTenantAndProperty();

        $oldCycle = PropertyAvailabilityCycle::create([
            'property_id' => $property->id,
            'available_from_at' => now()->subDays(10),
            'unavailable_at' => now()->subDays(1),
            'closed_by' => 'manual',
        ]);

        $activeCycle = PropertyAvailabilityCycle::create([
            'property_id' => $property->id,
            'available_from_at' => now(),
            'unavailable_at' => null,
            'closed_by' => null,
        ]);

        RentalRequest::create([
            'property_id' => $property->id,
            'availability_cycle_id' => $oldCycle->id,
            'tenant_id' => $tenant->id,
            'status' => 'cancelled_by_agent',
            'cancelled_at' => now()->subDay(),
            'message' => 'Cancelled by agent in previous cycle.',
        ]);

        $response = $this->actingAs($tenant)
            ->from('/houses/' . $property->id)
            ->post('/houses/' . $property->id . '/apply', [
                'message' => 'Applying after agent cancellation in old cycle.',
            ]);

        $response->assertSessionHas('success', 'Rental request submitted');

        $this->assertDatabaseHas('rental_requests', [
            'property_id' => $property->id,
            'availability_cycle_id' => $activeCycle->id,
            'tenant_id' => $tenant->id,
            'status' => 'pending_review',
            'message' => 'Applying after agent cancellation in old cycle.',
        ]);
    }

    public function test_it_allows_new_application_when_agent_cancelled_request_exists_in_active_cycle(): void
    {
        [$tenant, $property] = $this->createTenantAndProperty();

        $activeCycle = PropertyAvailabilityCycle::create([
            'property_id' => $property->id,
            'available_from_at' => now(),
            'unavailable_at' => null,
            'closed_by' => null,
        ]);

        RentalRequest::create([
            'property_id' => $property->id,
            'availability_cycle_id' => $activeCycle->id,
            'tenant_id' => $tenant->id,
            'status' => 'cancelled_by_agent',
            'cancelled_at' => now()->subMinute(),
            'message' => 'Cancelled by agent in active cycle.',
        ]);

        $response = $this->actingAs($tenant)
            ->from('/houses/' . $property->id)
            ->post('/houses/' . $property->id . '/apply', [
                'message' => 'Reapplying after agent cancellation in active cycle.',
            ]);

        $response->assertSessionHas('success', 'Rental request submitted');

        $this->assertEquals(2, RentalRequest::query()
            ->where('property_id', $property->id)
            ->where('availability_cycle_id', $activeCycle->id)
            ->where('tenant_id', $tenant->id)
            ->count());

        $this->assertDatabaseHas('rental_requests', [
            'property_id' => $property->id,
            'availability_cycle_id' => $activeCycle->id,
            'tenant_id' => $tenant->id,
            'status' => 'pending_review',
            'message' => 'Reapplying after agent cancellation in active cycle.',
        ]);
    }

    private function createTenantAndProperty(): array
    {
        $tenant = User::factory()->create([
            'role' => 'tenant',
            'enabled' => true,
        ]);

        $agent = User::factory()->create([
            'role' => 'agent',
            'enabled' => true,
        ]);

        $property = Property::create([
            'agent_id' => $agent->id,
            'title' => 'Cycle Test House',
            'address' => 'Jl. Contoh No. 1',
            'bedrooms' => 2,
            'bathrooms' => 1.0,
            'area' => 50,
            'building_area' => 45,
            'rent_price' => 3500000,
            'status' => 'to-let',
        ]);

        return [$tenant, $property];
    }
}
