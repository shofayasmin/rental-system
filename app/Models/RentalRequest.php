<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Property;
use App\Models\User;
use App\Models\PropertyAvailabilityCycle;

class RentalRequest extends Model
{
    protected $appends = [
        'lifecycle_status',
        'is_initial_payment_expired',
    ];

    protected $fillable = [
        'property_id',
        'availability_cycle_id',
        'tenant_id',
        'status',
        'awaiting_payment_at',
        'payment_due_at',
        'paid_at',
        'rejected_at',
        'cancelled_at',
    ];

    protected $casts = [
        'awaiting_payment_at' => 'datetime',
        'payment_due_at' => 'datetime',
        'paid_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function getLifecycleStatusAttribute(): string
    {
        if ($this->status === 'awaiting_payment' && $this->isInitialPaymentExpired()) {
            return 'expired';
        }

        return $this->status;
    }

    public function getIsInitialPaymentExpiredAttribute(): bool
    {
        return $this->isInitialPaymentExpired();
    }

    private function isInitialPaymentExpired(): bool
    {
        return $this->status === 'awaiting_payment'
            && $this->payment_due_at !== null
            && $this->payment_due_at->isPast();
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function availabilityCycle()
    {
        return $this->belongsTo(PropertyAvailabilityCycle::class);
    }

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class)
            ->where('type', 'initial_rent');
    }

    public function conversation()
    {
        return $this->hasOne(Conversation::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function activeContract()
    {
        return $this->hasOne(Contract::class)
            ->where('status', 'active')
            ->latestOfMany();
    }

}
