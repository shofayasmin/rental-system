<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\RentalRequest;
use App\Models\PropertyAvailabilityCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use App\Models\ContractExtension;
use App\Models\Contract;


class RentalRequestController extends Controller
{
    public function store(Request $request, Property $property)
    {
        if (Auth::user()->role !== 'tenant') {
            abort(403);
        }

        $result = DB::transaction(function () use ($property, $request) {
            $lockedProperty = Property::whereKey($property->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedProperty->status !== 'to-let') {
                return ['state' => 'unavailable'];
            }

            $activeCycle = PropertyAvailabilityCycle::query()
                ->where('property_id', $lockedProperty->id)
                ->whereNull('unavailable_at')
                ->orderByDesc('available_from_at')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (!$activeCycle) {
                $activeCycle = PropertyAvailabilityCycle::create([
                    'property_id' => $lockedProperty->id,
                    'available_from_at' => now(),
                    'unavailable_at' => null,
                    'closed_by' => null,
                ]);
            }

            $hasBlockingRequest = RentalRequest::query()
                ->where('property_id', $lockedProperty->id)
                ->where('availability_cycle_id', $activeCycle->id)
                ->where('tenant_id', Auth::id())
                ->whereIn('status', ['pending_review', 'awaiting_payment', 'paid'])
                ->exists();

            if ($hasBlockingRequest) {
                return ['state' => 'already_applied'];
            }

            $rentalRequest = RentalRequest::create([
                'property_id' => $lockedProperty->id,
                'availability_cycle_id' => $activeCycle->id,
                'tenant_id'   => Auth::id(),
                'status'      => 'pending_review',
            ]);

            return [
                'state' => 'created',
                'request' => $rentalRequest,
            ];
        });

        if (($result['state'] ?? null) === 'already_applied') {
            return back()->with('warning', 'You already applied for this property.');
        }

        if (($result['state'] ?? null) === 'unavailable') {
            return back()->with('error', 'This property is no longer available.');
        }

        return back()->with('success', 'Rental request submitted');
    }

    public function index(Request $request)
    {
        $tenantSearch = trim((string) $request->query('q', ''));
        $now = now();
        $rawClosedStatuses = ['rejected', 'cancelled_by_tenant', 'cancelled_by_agent', 'cancelled_lost'];
        $allowedTabs = ['in_progress', 'active_lease', 'closed', 'extensions'];
        $tab = (string) $request->query('tab', 'in_progress');
        if (!in_array($tab, $allowedTabs, true)) {
            $tab = 'in_progress';
        }

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
                'payment_expired' => 'PAYMENT EXPIRED',
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
            ->whereHas('property', function ($propertyQuery) {
                $propertyQuery->where('agent_id', Auth::id());
            });

        $extensionBaseCountQuery = ContractExtension::query()
            ->whereHas('contract.rentalRequest.property', function ($propertyQuery) {
                $propertyQuery->where('agent_id', Auth::id());
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

        $statusOptions = $statusOptionsByTab[$tab] ?? [];
        $statusFilter = (string) $request->query('status', '');
        if ($tab === 'closed' && $statusFilter === 'expired') {
            $statusFilter = 'payment_expired';
        }
        $needsActionOnly = $request->boolean('needs_action');

        $hasActiveAwaitingPaymentByProperty = RentalRequest::query()
            ->whereHas('property', function ($query) {
                $query->where('agent_id', Auth::id());
            })
            ->where('status', 'awaiting_payment')
            ->where(function ($query) use ($now) {
                $query->whereNull('payment_due_at')
                    ->orWhere('payment_due_at', '>', $now);
            })
            ->pluck('id', 'property_id')
            ->map(fn ($id) => (int) $id);

        $requests = collect();
        $extensions = collect();

        if ($tab !== 'extensions') {
            $requestsQuery = RentalRequest::query()
                ->with([
                    'tenant:id,name',
                    'property:id,title,address,agent_id,regency_id,district_id,rent_price',
                    'property.regency:id,name',
                    'property.district:id,name',
                    'transaction:id,rental_request_id,status,type,amount',
                    'activeContract' => function ($query) {
                        $query->select([
                            'contracts.id',
                            'contracts.rental_request_id',
                            'contracts.status',
                            'contracts.end_date',
                            'contracts.monthly_rent',
                        ]);
                    },
                    'activeContract.latestExtension' => function ($query) {
                        $query->select([
                            'contract_extensions.id',
                            'contract_extensions.contract_id',
                            'contract_extensions.status',
                            'contract_extensions.amount',
                            'contract_extensions.months_requested',
                            'contract_extensions.old_end_date',
                            'contract_extensions.paid_at',
                            'contract_extensions.payment_due_at',
                            'contract_extensions.new_end_date',
                            'contract_extensions.monthly_rent_snapshot',
                        ]);
                    },
                    'activeContract.latestExtension.transaction:id,contract_extension_id,status,type,amount',
                ])
                ->whereHas('property', function ($propertyQuery) {
                    $propertyQuery->where('agent_id', Auth::id());
                });

            if ($tab === 'active_lease') {
                $requestsQuery->where('status', 'paid')
                    ->whereHas('activeContract');
            } elseif ($tab === 'closed') {
                $requestsQuery->where($closedRequestsQuery);
            } else {
                $requestsQuery->where($openRequestsQuery);
            }

            if ($tenantSearch !== '') {
                $requestsQuery->where(function ($query) use ($tenantSearch) {
                    $query->whereHas('tenant', function ($tenantQuery) use ($tenantSearch) {
                        $tenantQuery->where('name', 'like', '%' . $tenantSearch . '%');
                    })->orWhereHas('property', function ($propertyQuery) use ($tenantSearch) {
                        $propertyQuery->where('title', 'like', '%' . $tenantSearch . '%')
                            ->orWhere('address', 'like', '%' . $tenantSearch . '%')
                            ->orWhereHas('regency', function ($regencyQuery) use ($tenantSearch) {
                                $regencyQuery->where('name', 'like', '%' . $tenantSearch . '%');
                            })
                            ->orWhereHas('district', function ($districtQuery) use ($tenantSearch) {
                                $districtQuery->where('name', 'like', '%' . $tenantSearch . '%');
                            });
                    });
                });
            }

            if ($statusFilter !== '') {
                if ($tab === 'closed' && $statusFilter === 'payment_expired') {
                    $requestsQuery->where(function ($query) use ($now) {
                        $query->where('status', 'cancelled_lost')
                            ->orWhere(function ($expiredQuery) use ($now) {
                                $expiredQuery->where('status', 'awaiting_payment')
                                    ->whereNotNull('payment_due_at')
                                    ->where('payment_due_at', '<=', $now);
                            });
                    });
                } elseif (in_array($statusFilter, $tabStatuses[$tab] ?? [], true)) {
                    $requestsQuery->where('status', $statusFilter);
                }
            }

            if ($needsActionOnly && $tab === 'in_progress') {
                $requestsQuery->whereIn('status', ['pending_review', 'awaiting_payment']);
            }

            $requests = $requestsQuery
                ->orderBy('created_at', $sortDirection)
                ->orderBy('id', $sortDirection)
                ->get();
        } else {
            $statusOptions = $extensionStatusLabelMap;
            $extensionsQuery = ContractExtension::query()
                ->with([
                    'contract:id,rental_request_id,end_date,monthly_rent,status',
                    'contract.rentalRequest:id,tenant_id,property_id,status,created_at,awaiting_payment_at,payment_due_at,paid_at,rejected_at,cancelled_at',
                    'contract.rentalRequest.tenant:id,name',
                    'contract.rentalRequest.property:id,title,address,agent_id,regency_id,district_id,rent_price',
                    'contract.rentalRequest.property.regency:id,name',
                    'contract.rentalRequest.property.district:id,name',
                    'transaction:id,contract_extension_id,status,type,amount',
                ])
                ->whereHas('contract.rentalRequest.property', function ($propertyQuery) {
                    $propertyQuery->where('agent_id', Auth::id());
                })
                ->whereIn('status', ['pending', 'awaiting_payment']);

            if ($tenantSearch !== '') {
                $extensionsQuery->where(function ($query) use ($tenantSearch) {
                    $query->whereHas('contract.rentalRequest.tenant', function ($tenantQuery) use ($tenantSearch) {
                        $tenantQuery->where('name', 'like', '%' . $tenantSearch . '%');
                    })->orWhereHas('contract.rentalRequest.property', function ($propertyQuery) use ($tenantSearch) {
                        $propertyQuery->where('title', 'like', '%' . $tenantSearch . '%')
                            ->orWhere('address', 'like', '%' . $tenantSearch . '%')
                            ->orWhereHas('regency', function ($regencyQuery) use ($tenantSearch) {
                                $regencyQuery->where('name', 'like', '%' . $tenantSearch . '%');
                            })
                            ->orWhereHas('district', function ($districtQuery) use ($tenantSearch) {
                                $districtQuery->where('name', 'like', '%' . $tenantSearch . '%');
                            });
                    });
                });
            }

            if ($statusFilter !== '' && in_array($statusFilter, array_keys($extensionStatusLabelMap), true)) {
                $extensionsQuery->where('status', $statusFilter);
            }

            if ($needsActionOnly) {
                $extensionsQuery->whereIn('status', ['pending', 'awaiting_payment']);
            }

            $extensions = $extensionsQuery
                ->orderBy('created_at', $sortDirection)
                ->orderBy('id', $sortDirection)
                ->get();
        }

        return view('agent.requests.index', compact(
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
            'now',
            'hasActiveAwaitingPaymentByProperty',
        ));
    }

    public function approve(RentalRequest $request)
    {
        $request->loadMissing('property');

        if ($request->property->agent_id !== Auth::id()) {
            abort(403);
        }

        if ($request->property->status === 'rented') {
            return back()->with('error', 'This property has already been rented.');
        }

        if ($request->status !== 'pending_review') {
            return back()->with('error', 'Only pending-review requests can be approved.');
        }

        DB::transaction(function () use ($request) {
            $now = now();
            $lockedRequest = RentalRequest::whereKey($request->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status !== 'pending_review') {
                return;
            }

            $hasActiveLock = RentalRequest::where('property_id', $lockedRequest->property_id)
                ->where('id', '!=', $lockedRequest->id)
                ->where('status', 'awaiting_payment')
                ->where(function ($query) use ($now) {
                    $query->whereNull('payment_due_at')
                        ->orWhere('payment_due_at', '>', $now);
                })
                ->lockForUpdate()
                ->exists();

            if ($hasActiveLock) {
                return;
            }

            $lockedRequest->update([
                'status' => 'awaiting_payment',
                'awaiting_payment_at' => now(),
                'payment_due_at' => now()->addDays(7),
            ]);

            Transaction::updateOrCreate(
                [
                    'rental_request_id' => $lockedRequest->id,
                    'type' => 'initial_rent',
                ],
                [
                    'property_id' => $lockedRequest->property_id,
                    'tenant_id' => $lockedRequest->tenant_id,
                    'agent_id' => $lockedRequest->property->agent_id,
                    'contract_extension_id' => null,
                    'amount' => $lockedRequest->property->rent_price,
                    'status' => 'unpaid',
                ]
            );
        });

        $request->refresh();
        if ($request->status === 'pending_review') {
            return back()->with('error', 'Another applicant is currently in payment stage for this property.');
        }

        return back()->with('success', 'Request approved. Tenant can proceed to payment.');
    }

    public function reject(RentalRequest $request)
    {
        $request->loadMissing('property');

        if ($request->property->agent_id !== Auth::id()) {
            abort(403);
        }

        if ($request->status !== 'pending_review') {
            return back()->with('error', 'Only pending-review requests can be rejected.');
        }

        $rejected = false;
        DB::transaction(function () use ($request, &$rejected) {
            $lockedRequest = RentalRequest::whereKey($request->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status !== 'pending_review') {
                return;
            }

            $lockedRequest->update([
                'status' => 'rejected',
                'rejected_at' => now(),
            ]);

            $rejected = true;
        });

        if (!$rejected) {
            return back()->with('error', 'Request can no longer be rejected.');
        }

        Transaction::where('rental_request_id', $request->id)
            ->where('type', 'initial_rent')
            ->where('status', 'unpaid')
            ->update(['status' => 'failed']);

        return back()->with('success', 'Request rejected.');
    }

    public function cancelLock(RentalRequest $request)
    {
        $request->loadMissing('property');

        if ($request->property->agent_id !== Auth::id()) {
            abort(403);
        }

        if ($request->status !== 'awaiting_payment') {
            return back()->with('error', 'This request is not in payment stage.');
        }

        DB::transaction(function () use ($request) {
            $lockedRequest = RentalRequest::whereKey($request->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedRequest->update([
                'status' => 'cancelled_by_agent',
                'cancelled_at' => now(),
            ]);

            Transaction::where('rental_request_id', $lockedRequest->id)
                ->where('type', 'initial_rent')
                ->where('status', 'unpaid')
                ->update(['status' => 'failed']);
        });

        return back()->with('success', 'Payment lock cancelled.');
    }

    public function showAgent(RentalRequest $request)
    {
        $request->loadMissing([
            'tenant:id,name,email',
            'property.agent:id,name,email',
            'property.photos',
            'property.province',
            'property.regency',
            'property.district',
            'property.village',
            'transaction',
            'activeContract.extensions.transaction',
        ]);

        if ((int) data_get($request, 'property.agent_id') !== (int) Auth::id()) {
            abort(403);
        }

        $activeContract = $request->activeContract;
        $extensions = $activeContract ? $activeContract->extensions->sortByDesc('created_at') : collect();
        $latestExtension = $extensions->first();

        return view('agent.requests.show', [
            'rentalRequest' => $request,
            'activeContract' => $activeContract,
            'extensions' => $extensions,
            'latestExtension' => $latestExtension,
        ]);
    }
}
