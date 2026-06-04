<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class AdminTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['property.agent', 'tenant']);

        // search by transaction number (id)
        if ($request->filled('transaction_id')) {
            $query->where('id', $request->transaction_id);
        }

        // search by date
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        // tenant name
        if ($request->filled('tenant')) {
            $query->whereHas('tenant', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->tenant . '%');
            });
        }

        // property title
        if ($request->filled('property')) {
            $query->whereHas('property', function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->property . '%');
            });
        }

        // agent name
        if ($request->filled('agent')) {
            $query->whereHas('property.agent', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->agent . '%');
            });
        }

        $transactions = $query
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('admin.transactions.index', compact('transactions'));
    }
}
