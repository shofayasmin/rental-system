<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\Concerns\SpecSeederSupport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RentalWorkflowSeeder extends Seeder
{
    use SpecSeederSupport;

    public function run(): void
    {
        $config = $this->specConfig();

        $properties = Property::query()
            ->with(['agent', 'regency'])
            ->orderBy('id')
            ->get()
            ->values();

        $tenants = User::query()
            ->where('role', 'tenant')
            ->orderBy('id')
            ->get()
            ->values();

        $agents = User::query()
            ->where('role', 'agent')
            ->orderBy('id')
            ->get()
            ->values();

        $admins = User::query()
            ->where('role', 'admin')
            ->orderBy('id')
            ->get()
            ->values();

        if ($properties->isEmpty() || $tenants->isEmpty() || $agents->isEmpty()) {
            return;
        }

        $periodStart = Carbon::parse($config['period_start']);
        $periodEnd = Carbon::parse($config['period_end']);
        $loginDatePool = $this->buildDatePool($periodStart, $periodEnd);
        $workflowDatePool = $this->buildDatePool($periodStart, $periodEnd);
        $propertyCycleIds = DB::table('property_availability_cycles')
            ->orderBy('id')
            ->get()
            ->groupBy('property_id')
            ->map(fn ($rows) => (int) $rows->last()->id);

        $paidProperties = $properties->where('status', 'rented')->values();
        $toLetProperties = $properties->where('status', 'to-let')->values();
        $maintenanceProperties = $properties->where('status', 'maintenance')->values();

        $paidMonthPlans = $this->buildPaidMonthPlans($periodStart, $periodEnd);
        $usedTenantAgentPairs = [];
        $paidPropertyIndex = 0;

        foreach ($paidMonthPlans as $monthIndex => $monthPlan) {
            for ($iteration = 0; $iteration < $monthPlan['count']; $iteration++) {
                $property = $paidProperties[$paidPropertyIndex] ?? null;
                if (!$property) {
                    break 2;
                }

                $agent = $property->agent ?: $agents->firstWhere('id', $property->agent_id);
                if (!$agent) {
                    $paidPropertyIndex++;
                    continue;
                }

                $tenant = $this->allocateTenantForAgent(
                    $tenants,
                    (int) $agent->id,
                    $paidPropertyIndex + ($monthIndex * 17),
                    $usedTenantAgentPairs
                );

                $createdAt = $this->sampleDateTime($monthPlan['pool']);
                $rentDays = $this->timeToRentDays($property->regency?->name ?? $property->title, $property->province_id);

                if ($monthPlan['key'] === '2026-05') {
                    $rentDays = min($rentDays, 16);
                }

                $paidAt = $createdAt->copy()->addDays($rentDays);
                if ($paidAt->gt($periodEnd)) {
                    $paidAt = $periodEnd->copy()->subHour();
                }

                $awaitingPaymentAt = $createdAt->copy()->addDay();
                $paymentDueAt = $createdAt->copy()->addDays(max(21, $rentDays + 5));
                if ($paymentDueAt->lte($paidAt)) {
                    $paymentDueAt = $paidAt->copy()->addDays(3);
                }

                $requestAmount = round(((float) $property->rent_price) * 12, 2);
                $cycleId = (int) ($propertyCycleIds[$property->id] ?? 0);

                $rentalRequestId = DB::table('rental_requests')->insertGetId([
                    'property_id' => $property->id,
                    'availability_cycle_id' => $cycleId,
                    'tenant_id' => $tenant->id,
                    'status' => 'paid',
                    'awaiting_payment_at' => $awaitingPaymentAt->format('Y-m-d H:i:s'),
                    'payment_due_at' => $paymentDueAt->format('Y-m-d H:i:s'),
                    'paid_at' => $paidAt->format('Y-m-d H:i:s'),
                    'rejected_at' => null,
                    'cancelled_at' => null,
                    'created_at' => $createdAt->format('Y-m-d H:i:s'),
                    'updated_at' => $paidAt->format('Y-m-d H:i:s'),
                ]);

                DB::table('transactions')->insert([
                    'rental_request_id' => $rentalRequestId,
                    'property_id' => $property->id,
                    'tenant_id' => $tenant->id,
                    'agent_id' => $agent->id,
                    'contract_extension_id' => null,
                    'amount' => $requestAmount,
                    'type' => 'initial_rent',
                    'status' => 'paid',
                    'created_at' => $paidAt->format('Y-m-d H:i:s'),
                    'updated_at' => $paidAt->format('Y-m-d H:i:s'),
                ]);

                $contractEnd = $paidAt->copy()->addMonthsNoOverflow(14);
                $contractStatus = $contractEnd->lte(Carbon::now()) ? 'ended' : 'active';

                $contractId = DB::table('contracts')->insertGetId([
                    'rental_request_id' => $rentalRequestId,
                    'start_date' => $paidAt->toDateString(),
                    'end_date' => $contractEnd->toDateString(),
                    'monthly_rent' => $property->rent_price,
                    'total_price' => $requestAmount,
                    'status' => $contractStatus,
                    'ended_reason' => $contractStatus === 'ended' ? 'completed' : null,
                    'ended_by' => null,
                    'ended_at' => $contractStatus === 'ended' ? $contractEnd->copy()->endOfDay()->format('Y-m-d H:i:s') : null,
                    'created_at' => $paidAt->format('Y-m-d H:i:s'),
                    'updated_at' => $paidAt->format('Y-m-d H:i:s'),
                ]);

                $conversationId = $this->upsertConversation(
                    $property->id,
                    $tenant->id,
                    $agent->id,
                    $rentalRequestId,
                    'open',
                    $createdAt->format('Y-m-d H:i:s'),
                    $paidAt->format('Y-m-d H:i:s')
                );

                $this->seedConversationMessages(
                    $conversationId,
                    $tenant->id,
                    $agent->id,
                    $property->id,
                    $rentalRequestId,
                    $createdAt
                );

                $paidPropertyIndex++;
            }
        }

        $toLetWorkflowStatuses = [
            'pending_review', 'pending_review', 'pending_review', 'pending_review',
            'pending_review', 'pending_review', 'pending_review', 'pending_review',
            'pending_review', 'pending_review',
            'awaiting_payment', 'awaiting_payment', 'awaiting_payment', 'awaiting_payment',
            'awaiting_payment', 'awaiting_payment',
            'rejected', 'rejected', 'rejected', 'rejected',
            'cancelled_by_tenant', 'cancelled_by_tenant',
            'cancelled_by_agent', 'cancelled_by_agent',
        ];

        $toLetWorkflowProperties = $toLetProperties->take(count($toLetWorkflowStatuses));
        foreach ($toLetWorkflowProperties as $index => $property) {
            $agent = $property->agent ?: $agents->firstWhere('id', $property->agent_id);
            if (!$agent) {
                continue;
            }

            $status = $toLetWorkflowStatuses[$index];
            $tenant = $this->allocateTenantForAgent(
                $tenants,
                (int) $agent->id,
                1000 + $index,
                $usedTenantAgentPairs
            );

            $createdAt = $this->sampleDateTime($workflowDatePool);
            $awaitingPaymentAt = null;
            $paymentDueAt = null;
            $rejectedAt = null;
            $cancelledAt = null;
            $requestAmount = round(((float) $property->rent_price) * 12, 2);
            $cycleId = (int) ($propertyCycleIds[$property->id] ?? 0);

            if ($status === 'awaiting_payment') {
                $awaitingPaymentAt = $createdAt->copy()->addDay();
                $paymentDueAt = $createdAt->copy()->addDays(6);
            } elseif ($status === 'rejected') {
                $rejectedAt = $createdAt->copy()->addHours(12);
            } elseif (str_starts_with($status, 'cancelled_')) {
                $cancelledAt = $createdAt->copy()->addHours(10);
            }

            $rentalRequestId = DB::table('rental_requests')->insertGetId([
                'property_id' => $property->id,
                'availability_cycle_id' => $cycleId,
                'tenant_id' => $tenant->id,
                'status' => $status,
                'awaiting_payment_at' => $awaitingPaymentAt?->format('Y-m-d H:i:s'),
                'payment_due_at' => $paymentDueAt?->format('Y-m-d H:i:s'),
                'paid_at' => null,
                'rejected_at' => $rejectedAt?->format('Y-m-d H:i:s'),
                'cancelled_at' => $cancelledAt?->format('Y-m-d H:i:s'),
                'created_at' => $createdAt->format('Y-m-d H:i:s'),
                'updated_at' => ($cancelledAt ?? $rejectedAt ?? $paymentDueAt ?? $awaitingPaymentAt ?? $createdAt)
                    ->format('Y-m-d H:i:s'),
            ]);

            if ($status === 'awaiting_payment') {
                DB::table('transactions')->insert([
                    'rental_request_id' => $rentalRequestId,
                    'property_id' => $property->id,
                    'tenant_id' => $tenant->id,
                    'agent_id' => $agent->id,
                    'contract_extension_id' => null,
                    'amount' => $requestAmount,
                    'type' => 'initial_rent',
                    'status' => 'unpaid',
                    'created_at' => $awaitingPaymentAt->format('Y-m-d H:i:s'),
                    'updated_at' => $paymentDueAt->format('Y-m-d H:i:s'),
                ]);
            }

            $conversationStatus = in_array($status, ['pending_review', 'awaiting_payment'], true) ? 'open' : 'closed';
            $conversationId = $this->upsertConversation(
                $property->id,
                $tenant->id,
                $agent->id,
                $rentalRequestId,
                $conversationStatus,
                $createdAt->format('Y-m-d H:i:s'),
                ($cancelledAt ?? $rejectedAt ?? $paymentDueAt ?? $awaitingPaymentAt ?? $createdAt)
                    ->format('Y-m-d H:i:s')
            );

            $this->seedConversationMessages(
                $conversationId,
                $tenant->id,
                $agent->id,
                $property->id,
                $rentalRequestId,
                $createdAt
            );
        }

        $this->seedLoginAudits($agents, $tenants, $admins, $loginDatePool);
    }

    private function requestMessageForStatus(string $status, string $title): string
    {
        return match ($status) {
            'paid' => "Confirmed rental request for {$title}. Payment has been completed and the contract is ready.",
            'awaiting_payment' => "Rental request for {$title} has been approved and is waiting for payment.",
            'pending_review' => "Tenant is interested in {$title} and is waiting for agent review.",
            'rejected' => "Request for {$title} was reviewed but rejected by the agent.",
            'cancelled_by_tenant' => "Tenant cancelled the request for {$title} before it was completed.",
            'cancelled_by_agent' => "Agent cancelled the request for {$title} due to operational reasons.",
            'cancelled_lost' => "Payment deadline for {$title} expired before the tenant completed the payment.",
            default => "Rental request for {$title}.",
        };
    }

    private function buildPaidMonthPlans(Carbon $periodStart, Carbon $periodEnd): array
    {
        $plans = [];
        $cursor = $periodStart->copy()->startOfMonth();
        $seedStart = Carbon::parse('2025-05-01 00:00:00');
        if ($cursor->lt($seedStart)) {
            $cursor = $seedStart->copy();
        }
        $lastMonth = $periodEnd->copy()->startOfMonth();

        while ($cursor->lte($lastMonth)) {
            $monthKey = $cursor->format('Y-m');
            $monthPool = $this->buildDatePool($cursor->copy()->startOfMonth(), $cursor->copy()->endOfMonth()->endOfDay());

            if ($monthKey === '2026-05') {
                $monthPool = array_values(array_filter($monthPool, fn (Carbon $date) => (int) $date->day <= 10));
            }

            $plans[] = [
                'key' => $monthKey,
                'count' => match ($monthKey) {
                    '2025-05' => 8,
                    '2025-06', '2025-07', '2025-08' => 12,
                    '2025-09', '2025-10', '2026-02', '2026-03' => 9,
                    '2025-11', '2025-12' => 11,
                    '2026-01' => 12,
                    '2026-04' => 10,
                    '2026-05' => 12,
                    default => 10,
                },
                'pool' => $monthPool,
            ];

            $cursor->addMonth();
        }

        return $plans;
    }

    private function allocateTenantForAgent(
        Collection $tenants,
        int $agentId,
        int $seedOffset,
        array &$usedTenantAgentPairs
    ): User {
        $tenantCount = $tenants->count();

        for ($attempt = 0; $attempt < $tenantCount; $attempt++) {
            $tenant = $tenants[($seedOffset + $attempt) % $tenantCount];
            $pairKey = $tenant->id . ':' . $agentId;

            if (!isset($usedTenantAgentPairs[$pairKey])) {
                $usedTenantAgentPairs[$pairKey] = true;

                return $tenant;
            }
        }

        throw new \RuntimeException("Unable to allocate a unique tenant-agent pair for agent {$agentId}.");
    }

    private function requestStatusesForProperty(string $propertyStatus, int $requestCount): array
    {
        if ($requestCount <= 0) {
            return [];
        }

        $closedStatuses = ['rejected', 'cancelled_by_tenant', 'cancelled_by_agent', 'cancelled_lost'];

        return match ($propertyStatus) {
            'rented' => $this->buildRentedRequestStatuses($requestCount, $closedStatuses),
            'maintenance' => $this->buildMaintenanceRequestStatuses($requestCount, $closedStatuses),
            default => $this->buildToLetRequestStatuses($requestCount, $closedStatuses),
        };
    }

    private function buildRentedRequestStatuses(int $requestCount, array $closedStatuses): array
    {
        $statuses = ['paid'];

        while (count($statuses) < $requestCount) {
            $statuses[] = $closedStatuses[array_rand($closedStatuses)];
        }

        return $statuses;
    }

    private function buildMaintenanceRequestStatuses(int $requestCount, array $closedStatuses): array
    {
        $statuses = [];

        for ($index = 0; $index < $requestCount; $index++) {
            $statuses[] = $closedStatuses[array_rand($closedStatuses)];
        }

        return $statuses;
    }

    private function buildToLetRequestStatuses(int $requestCount, array $closedStatuses): array
    {
        $statuses = [];

        if ($requestCount >= 1) {
            $statuses[] = 'pending_review';
        }

        if ($requestCount >= 2) {
            $statuses[] = 'awaiting_payment';
        }

        while (count($statuses) < $requestCount) {
            $statuses[] = $closedStatuses[array_rand($closedStatuses)];
        }

        return $statuses;
    }

    private function seedConversationMessages(
        int $conversationId,
        int $tenantId,
        int $agentId,
        int $propertyId,
        int $rentalRequestId,
        Carbon $baseTime
    ): void {
        $messages = [
            [
                'sender_id' => $tenantId,
                'message' => 'Hello, I would like to ask about this property and its rental availability. ' . $this->requestMessageForStatus('pending_review', 'this property'),
                'offset_hours' => 0,
            ],
            [
                'sender_id' => $agentId,
                'message' => 'Thanks for your interest. The property is available and I can provide additional details.',
                'offset_hours' => 3,
            ],
            [
                'sender_id' => $tenantId,
                'message' => 'Thank you. I will continue with the next step in the rental process.',
                'offset_hours' => 5,
            ],
        ];

        foreach ($messages as $item) {
            $createdAt = $baseTime->copy()->addHours($item['offset_hours']);

            DB::table('conversation_messages')->insert([
                'conversation_id' => $conversationId,
                'sender_id' => $item['sender_id'],
                'property_id' => $propertyId,
                'rental_request_id' => $rentalRequestId,
                'message' => $item['message'],
                'created_at' => $createdAt->format('Y-m-d H:i:s'),
                'updated_at' => $createdAt->format('Y-m-d H:i:s'),
            ]);

            DB::table('conversations')
                ->where('id', $conversationId)
                ->update([
                    'last_message_at' => $createdAt->format('Y-m-d H:i:s'),
                    'updated_at' => $createdAt->format('Y-m-d H:i:s'),
                ]);
        }
    }

    private function seedLoginAudits(Collection $agents, Collection $tenants, Collection $admins, array $datePool): void
    {
        foreach ($admins as $index => $admin) {
            for ($i = 0; $i < 3; $i++) {
                $when = $this->sampleDateTime($datePool);
                DB::table('login_audit')->insert([
                    'user_id' => $admin->id,
                    'ip_address' => '192.168.30.' . ($index + $i + 10),
                    'user_agent' => $this->userAgents()[($index + $i) % count($this->userAgents())],
                    'logged_in_at' => $when->format('Y-m-d H:i:s'),
                ]);
            }
        }

        foreach ($agents as $index => $agent) {
            for ($i = 0; $i < 2; $i++) {
                $when = $this->sampleDateTime($datePool);
                DB::table('login_audit')->insert([
                    'user_id' => $agent->id,
                    'ip_address' => '192.168.10.' . ($index + $i + 10),
                    'user_agent' => $this->userAgents()[($index + $i) % count($this->userAgents())],
                    'logged_in_at' => $when->format('Y-m-d H:i:s'),
                ]);
            }
        }

        foreach ($tenants as $index => $tenant) {
            $when = $this->sampleDateTime($datePool);
            DB::table('login_audit')->insert([
                'user_id' => $tenant->id,
                'ip_address' => '192.168.20.' . ($index % 245 + 10),
                'user_agent' => $this->userAgents()[$index % count($this->userAgents())],
                'logged_in_at' => $when->format('Y-m-d H:i:s'),
            ]);
        }
    }

    private function upsertConversation(
        int $propertyId,
        int $tenantId,
        int $agentId,
        int $rentalRequestId,
        string $status,
        string $createdAt,
        string $updatedAt
    ): int {
        $existingId = DB::table('conversations')
            ->where('tenant_id', $tenantId)
            ->where('agent_id', $agentId)
            ->value('id');

        if ($existingId) {
            DB::table('conversations')
                ->where('id', $existingId)
                ->update([
                    'property_id' => $propertyId,
                    'rental_request_id' => $rentalRequestId,
                    'status' => $status,
                    'last_message_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ]);

            return (int) $existingId;
        }

        return (int) DB::table('conversations')->insertGetId([
            'property_id' => $propertyId,
            'tenant_id' => $tenantId,
            'agent_id' => $agentId,
            'rental_request_id' => $rentalRequestId,
            'status' => $status,
            'last_message_at' => $createdAt,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ]);
    }
}
