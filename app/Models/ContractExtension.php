<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ContractExtension extends Model
{
    protected $table = 'contract_extensions';

    protected $fillable = [
        'contract_id',
        'months_requested',
        'monthly_rent_snapshot',
        'amount',
        'status',
        'approved_by',
        'approved_at',
        'payment_due_at',
        'rejected_at',
        'cancelled_at',
        'paid_at',
        'old_end_date',
        'new_end_date',
        'extended_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'payment_due_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'paid_at' => 'datetime',
            'extended_at' => 'datetime',
        ];
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class, 'contract_extension_id');
    }
}
