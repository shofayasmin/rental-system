<?php

namespace App\Http\Controllers;

use App\Models\ContractExtension;
use App\Models\RentalRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class TenantRequestController extends Controller
{
    public function index(Request $request)
    {
        $now = now();
        $rawClosedStatuses = ['rejected', 'cancelled_by_tenant', 'cancelled_by_agent', 'cancelled_lost'];
        $allowedTabs = ['in_progress', 'active_lease', 'closed', 'extensions'];
        $tab = (string) $request->query('tab', 'in_progress');
        if (!in_array($tab, $allowedTabs, true)) {
            $tab = 'in_progress';
        }

        $tenantSearch = trim((string) $request->query('q', ''));
        $sort = (string) $request->query('sort', 'newest');
        if (!in_array($sort, ['newest', 'oldest'], true)) {
            $sort = 'newest';
        }
        $sortDirection = $sort === 'oldest' ? 'asc' : 'desc';
        $sortOptions = [
            'newest' => 'Newest',
            'oldest' => 'Oldest',
        ];

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

        $tabStatuses = [
            'in_progress' => ['pending_review', 'awaiting_payment'],
            'active_lease' => ['paid'],
            'closed' => array_merge($rawClosedStatuses, ['expired']),
        ];

        $statusOptionsByTab = [
            'in_progress' => [
                'pending_review' => 'REQUEST UNDER REVIEW',
                'awaiting_payment' => 'AWAITING INITIAL PAYMENT',
            ],
            'active_lease' => [
                'paid' => 'ACTIVE LEASE',
            ],
            'closed' => [
                'rejected' => 'REQUEST REJECTED BY AGENT',
                'cancelled_by_tenant' => 'REQUEST CANCELLED BY TENANT',
                'cancelled_by_agent' => 'REQUEST CANCELLED BY AGENT',
                'cancelled_lost' => 'PAYMENT EXPIRED',
                'expired' => 'PAYMENT EXPIRED',
            ],
        ];

        $extensionStatusLabelMap = [
            'pending' => 'PENDING',
            'awaiting_payment' => 'AWAITING PAYMENT',
            'paid' => 'PAID',
            'rejected' => 'REJECTED',
            'cancelled_by_tenant' => 'CANCELLED BY TENANT',
            'cancelled_by_agent' => 'CANCELLED BY AGENT',
            'expired' => 'EXPIRED',
        ];

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

        $requestQuery = RentalRequest::query()
            ->with([
                'property:id,title,address,regency_id,district_id,rent_price',
                'property.regency:id,name',
                'property.district:id,name',
                'transaction:id,rental_request_id,status,type,amount',
                'activeContract' => function ($contractQuery) {
                    $contractQuery->select([
                        'contracts.id',
                        'contracts.rental_request_id',
                        'contracts.status',
                        'contracts.end_date',
                        'contracts.monthly_rent',
                    ]);
                },
                'activeContract.latestExtension' => function ($extensionQuery) {
                    $extensionQuery->select([
                        'contract_extensions.id',
                        'contract_extensions.contract_id',
                        'contract_extensions.status',
                        'contract_extensions.amount',
                        'contract_extensions.months_requested',
                        'contract_extensions.old_end_date',
                        'contract_extensions.new_end_date',
                        'contract_extensions.payment_due_at',
                        'contract_extensions.monthly_rent_snapshot',
                    ]);
                },
                'activeContract.latestExtension.transaction:id,contract_extension_id,status,type,amount',
            ])
            ->where('tenant_id', Auth::id());

        $extensions = collect();

        if ($tab !== 'extensions') {
            if ($tab === 'active_lease') {
                $requestQuery->where('status', 'paid')
                    ->whereHas('activeContract');
            } elseif ($tab === 'closed') {
                $requestQuery->where($closedRequestsQuery);
            } else {
                $requestQuery->where($openRequestsQuery);
            }

            if ($tenantSearch !== '') {
                $requestQuery->whereHas('property', function ($propertyQuery) use ($tenantSearch) {
                    $propertyQuery->where('title', 'like', '%' . $tenantSearch . '%')
                        ->orWhere('address', 'like', '%' . $tenantSearch . '%')
                        ->orWhereHas('regency', function ($regencyQuery) use ($tenantSearch) {
                            $regencyQuery->where('name', 'like', '%' . $tenantSearch . '%');
                        })
                        ->orWhereHas('district', function ($districtQuery) use ($tenantSearch) {
                            $districtQuery->where('name', 'like', '%' . $tenantSearch . '%');
                        });
                });
            }

            $statusOptions = $statusOptionsByTab[$tab] ?? [];
            $statusFilter = (string) $request->query('status', '');
            if ($statusFilter !== '') {
                if ($tab === 'closed' && $statusFilter === 'expired') {
                    $requestQuery->where('status', 'awaiting_payment')
                        ->whereNotNull('payment_due_at')
                        ->where('payment_due_at', '<=', $now);
                } elseif (in_array($statusFilter, $tabStatuses[$tab] ?? [], true)) {
                    $requestQuery->where('status', $statusFilter);
                }
            }

            $requests = $requestQuery
                ->orderBy('created_at', $sortDirection)
                ->orderBy('id', $sortDirection)
                ->get();
        } else {
            $statusOptions = $extensionStatusLabelMap;
            $statusFilter = (string) $request->query('status', '');
            $extensionQuery = ContractExtension::query()
                ->with([
                    'contract:id,rental_request_id,end_date,monthly_rent,status',
                    'contract.rentalRequest:id,tenant_id,property_id,status,created_at,awaiting_payment_at,payment_due_at,paid_at,rejected_at,cancelled_at',
                    'contract.rentalRequest.property:id,title,address,regency_id,district_id,rent_price',
                'contract.rentalRequest.property.regency:id,name',
                'contract.rentalRequest.property.district:id,name',
                'transaction:id,contract_extension_id,status,type,amount',
                ])
                ->whereHas('contract.rentalRequest', function ($requestQuery) {
                    $requestQuery->where('tenant_id', Auth::id());
                })
                ->whereIn('status', ['pending', 'awaiting_payment']);

            if ($tenantSearch !== '') {
                $extensionQuery->whereHas('contract.rentalRequest.property', function ($propertyQuery) use ($tenantSearch) {
                    $propertyQuery->where('title', 'like', '%' . $tenantSearch . '%')
                        ->orWhere('address', 'like', '%' . $tenantSearch . '%')
                        ->orWhereHas('regency', function ($regencyQuery) use ($tenantSearch) {
                            $regencyQuery->where('name', 'like', '%' . $tenantSearch . '%');
                        })
                        ->orWhereHas('district', function ($districtQuery) use ($tenantSearch) {
                            $districtQuery->where('name', 'like', '%' . $tenantSearch . '%');
                        });
                });
            }

            if ($statusFilter !== '' && in_array($statusFilter, array_keys($extensionStatusLabelMap), true)) {
                $extensionQuery->where('status', $statusFilter);
            }

            if ($request->boolean('needs_action')) {
                $extensionQuery->whereIn('status', ['pending', 'awaiting_payment']);
            }

            $extensions = $extensionQuery
                ->orderBy('created_at', $sortDirection)
                ->orderBy('id', $sortDirection)
                ->get();

            $requests = collect();
        }

        $needsActionOnly = $request->boolean('needs_action');

        return view('tenant.requests.index', compact(
            'tab',
            'tabCounts',
            'requests',
            'extensions',
            'tenantSearch',
            'statusFilter',
            'needsActionOnly',
            'statusOptions',
            'sort',
            'sortOptions',
            'extensionStatusLabelMap',
            'now'
        ));
    }
}
