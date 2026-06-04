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
        $selectedPropertyId = $request->query('property');
        $selectedPropertyId = $selectedPropertyId !== null ? (int) $selectedPropertyId : null;
        $tenantSearch = trim((string) $request->query('q', ''));
        $applicantStatus = (string) $request->query('applicant_status', '');
        $needsActionOnly = $request->boolean('needs_action');
        $now = now();

        $propertyQuery = Property::query()
            ->where('agent_id', Auth::id())
            ->whereHas('rentalRequests')
            ->with(['regency', 'district'])
            ->withCount([
                'rentalRequests as pending_review_count' => function ($query) {
                    $query->where('status', 'pending_review');
                },
                'rentalRequests as awaiting_payment_count' => function ($query) {
                    $query->where('status', 'awaiting_payment');
                },
                'rentalRequests as paid_count' => function ($query) {
                    $query->where('status', 'paid');
                },
                'rentalRequests as total_requests_count',
            ])
            ->withMax('rentalRequests as last_request_activity_at', 'created_at');

        if ($tenantSearch !== '') {
            $propertyQuery->where(function ($query) use ($tenantSearch) {
                $query->where('title', 'like', '%' . $tenantSearch . '%')
                    ->orWhere('address', 'like', '%' . $tenantSearch . '%')
                    ->orWhereHas('regency', function ($regencyQuery) use ($tenantSearch) {
                        $regencyQuery->where('name', 'like', '%' . $tenantSearch . '%');
                    })
                    ->orWhereHas('district', function ($districtQuery) use ($tenantSearch) {
                        $districtQuery->where('name', 'like', '%' . $tenantSearch . '%');
                    });
            });
        }

        $properties = $propertyQuery
            ->orderByDesc('last_request_activity_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $selectedProperty = null;
        if ($selectedPropertyId) {
            $selectedProperty = (clone $propertyQuery)
                ->whereKey($selectedPropertyId)
                ->first();
        }

        if (!$selectedProperty) {
            $selectedProperty = $properties->getCollection()->first();
        }

        if ($selectedProperty) {
            $selectedProperty->loadMissing(['regency', 'district']);
        }

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

        $applicants = collect();
        if ($selectedProperty) {
            $applicantsQuery = RentalRequest::query()
                ->with([
                    'tenant:id,name',
                    'property:id,title,address,agent_id,regency_id',
                    'property.regency:id,name',
                    'property.district:id,name',
                    'transaction:id,rental_request_id,status,type,amount',
                    'activeContract' => function ($query) {
                        $query->select([
                            'contracts.id',
                            'contracts.rental_request_id',
                            'contracts.status',
                            'contracts.end_date',
                        ]);
                    },
                    'activeContract.latestExtension' => function ($query) {
                        $query->select([
                            'contract_extensions.id',
                            'contract_extensions.contract_id',
                            'contract_extensions.status',
                            'contract_extensions.paid_at',
                            'contract_extensions.new_end_date',
                        ]);
                    },
                ])
                ->where('property_id', $selectedProperty->id);

            if ($applicantStatus !== '') {
                $applicantsQuery->where('status', $applicantStatus);
            }

            if ($needsActionOnly) {
                $applicantsQuery->whereIn('status', ['pending_review', 'awaiting_payment']);
            }

            $applicants = $applicantsQuery
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(10, ['*'], 'applicants_page')
                ->withQueryString();
        }

        $pendingExtensions = ContractExtension::query()
            ->with([
                'contract.rentalRequest.property:id,title,address,agent_id,regency_id',
                'contract.rentalRequest.tenant:id,name',
            ])
            ->whereHas('contract.rentalRequest.property', function ($query) {
                $query->where('agent_id', Auth::id());
            })
            ->whereIn('status', ['pending', 'awaiting_payment'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return view('agent.requests.index', compact(
            'properties',
            'selectedPropertyId',
            'selectedProperty',
            'tenantSearch',
            'applicantStatus',
            'needsActionOnly',
            'applicants',
            'hasActiveAwaitingPaymentByProperty',
            'pendingExtensions',
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
