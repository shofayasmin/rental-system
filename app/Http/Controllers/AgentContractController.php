<?php

namespace App\Http\Controllers;

use App\Models\ContractExtension;
use App\Models\RentalRequest;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgentContractController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $now = now();
        $rawClosedStatuses = ['rejected', 'cancelled_by_tenant', 'cancelled_by_agent', 'cancelled_lost'];

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

        $contractsQuery = Transaction::with([
                'property',
                'property.province',
                'property.regency',
                'property.district',
                'property.village',
                'contract',
                'contractExtension.contract',
                'tenant:id,name',
            ])
            ->where('agent_id', Auth::id())
            ->whereIn('type', ['initial_rent', 'extension_rent'])
            ->where('status', 'paid');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $contractsQuery->where(function ($query) use ($like) {
                $query->whereHas('property', function ($propertyQuery) use ($like) {
                    $propertyQuery->where('title', 'like', $like)
                        ->orWhere('address', 'like', $like)
                        ->orWhereHas('regency', function ($regencyQuery) use ($like) {
                            $regencyQuery->where('name', 'like', $like);
                        });
                })->orWhereHas('tenant', function ($tenantQuery) use ($like) {
                    $tenantQuery->where('name', 'like', $like);
                });
            });
        }

        $contracts = $contractsQuery
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $requestBaseCountQuery = RentalRequest::query()
            ->whereHas('property', function ($query) {
                $query->where('agent_id', Auth::id());
            });

        $extensionBaseCountQuery = ContractExtension::query()
            ->whereHas('contract.rentalRequest.property', function ($query) {
                $query->where('agent_id', Auth::id());
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

        return view('agent.contracts.index', compact('contracts', 'tabCounts', 'search'));
    }
}
