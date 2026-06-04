<?php

namespace App\Http\Controllers;

use App\Models\ContractExtension;
use App\Models\RentalRequest;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantContractController extends Controller
{
    public function index(Request $request)
    {
        $now = now();
        $rawClosedStatuses = ['rejected', 'cancelled_by_tenant', 'cancelled_by_agent', 'cancelled_lost'];
        $tab = (string) $request->query('tab', 'contracts');
        $allowedTabs = ['in_progress', 'active_lease', 'closed', 'extensions', 'contracts'];
        if (!in_array($tab, $allowedTabs, true)) {
            $tab = 'contracts';
        }

        $openRequestsQuery = function ($query) use ($now) {
            $query->where('status', 'pending_review')
                ->orWhere(function ($awaitingQuery) use ($now) {
                    $awaitingQuery->where('status', 'awaiting_payment')
                        ->where(function ($dueQuery) use ($now) {
                            $dueQuery->whereNull('payment_due_at')
                                ->orWhere('payment_due_at', '>', $now);
                        });
                });
        };

        $closedRequestsQuery = function ($query) use ($now, $rawClosedStatuses) {
            $query->whereIn('status', $rawClosedStatuses)
                ->orWhere(function ($expiredQuery) use ($now) {
                    $expiredQuery->where('status', 'awaiting_payment')
                        ->whereNotNull('payment_due_at')
                        ->where('payment_due_at', '<=', $now);
                })
                ->orWhere(function ($endedLeaseQuery) {
                    $endedLeaseQuery->where('status', 'paid')
                        ->whereDoesntHave('activeContract');
                });
        };

        $requestBaseCountQuery = RentalRequest::query()
            ->where('tenant_id', Auth::id());

        $extensionBaseCountQuery = ContractExtension::query()
            ->whereHas('contract.rentalRequest', function ($requestQuery) {
                $requestQuery->where('tenant_id', Auth::id());
            });

        $tabCounts = [
            'in_progress' => (clone $requestBaseCountQuery)
                ->where($openRequestsQuery)
                ->count(),
            'active_lease' => (clone $requestBaseCountQuery)
                ->where('status', 'paid')
                ->whereHas('activeContract')
                ->count(),
            'closed' => (clone $requestBaseCountQuery)
                ->where($closedRequestsQuery)
                ->count(),
            'extensions' => (clone $extensionBaseCountQuery)
                ->whereIn('status', ['pending', 'awaiting_payment'])
                ->count(),
        ];

        $search = trim((string) $request->query('q', ''));

        $contracts = Transaction::query()
            ->with([
                'property:id,title,address,regency_id,district_id,rent_price,agent_id',
                'property.regency:id,name',
                'property.district:id,name',
                'property.agent:id,name',
                'contractExtension:id,old_end_date,new_end_date,monthly_rent_snapshot,amount,status',
                'contract' => function ($contractQuery) {
                    $contractQuery->select([
                        'contracts.id',
                        'contracts.rental_request_id',
                        'contracts.status',
                        'contracts.start_date',
                        'contracts.end_date',
                        'contracts.monthly_rent',
                        'contracts.total_price',
                    ]);
                },
                'contract.rentalRequest:id,tenant_id,property_id,status,created_at,awaiting_payment_at,payment_due_at,paid_at,rejected_at,cancelled_at',
                'contract.latestExtension' => function ($extensionQuery) {
                    $extensionQuery->select([
                        'contract_extensions.id',
                        'contract_extensions.contract_id',
                        'contract_extensions.status',
                        'contract_extensions.amount',
                        'contract_extensions.payment_due_at',
                        'contract_extensions.old_end_date',
                        'contract_extensions.new_end_date',
                        'contract_extensions.monthly_rent_snapshot',
                    ]);
                },
            ])
            ->where('tenant_id', Auth::id())
            ->whereIn('type', ['initial_rent', 'extension_rent'])
            ->where('status', 'paid')
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('property', function ($propertyQuery) use ($search) {
                    $propertyQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhereHas('regency', function ($regencyQuery) use ($search) {
                            $regencyQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('district', function ($districtQuery) use ($search) {
                            $districtQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('agent', function ($agentQuery) use ($search) {
                            $agentQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('updated_at')
            ->orderBy('id')
            ->get();

        return view('tenant.contracts.index', compact('contracts', 'tab', 'tabCounts', 'search'));
    }
}
