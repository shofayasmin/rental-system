<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Contract extends Model
{
    protected $fillable = [
        'rental_request_id',
        'start_date',
        'end_date',
        'monthly_rent',
        'total_price',
        'status',
        'ended_reason',
        'ended_by',
        'ended_at',
    ];

    protected $casts = [
        'ended_at' => 'datetime',
    ];

    public function setStatusAttribute($value)
    {
        $allowedStatuses = ['active', 'ended'];

        if (!in_array($value, $allowedStatuses, true)) {
            return;
        }

        if ($this->exists && $this->getOriginal('status') === 'ended' && $value !== 'ended') {
            return;
        }

        $this->attributes['status'] = $value;
    }

    public function rentalRequest()
    {
        return $this->belongsTo(RentalRequest::class);
    }

    public function extensions()
    {
        return $this->hasMany(ContractExtension::class);
    }

    public function latestExtension()
    {
        return $this->hasOne(ContractExtension::class)->latestOfMany();
    }

    public function endedBy()
    {
        return $this->belongsTo(User::class, 'ended_by');
    }

    public function isActive()
    {
        return $this->status === 'active';
    }

}
