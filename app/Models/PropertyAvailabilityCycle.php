<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyAvailabilityCycle extends Model
{
    protected $fillable = [
        'property_id',
        'available_from_at',
        'unavailable_at',
        'closed_by',
    ];

    protected $casts = [
        'available_from_at' => 'datetime',
        'unavailable_at' => 'datetime',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function rentalRequests()
    {
        return $this->hasMany(RentalRequest::class, 'availability_cycle_id');
    }
}
