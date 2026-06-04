<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Facility;
use App\Models\PropertyPhoto;
use App\Models\User;
use App\Models\PropertyAvailabilityCycle;
use App\Models\Province;
use App\Models\Regency;
use App\Models\District;
use App\Models\Village;

class Property extends Model
{
    protected $fillable = [
        'agent_id',
        'title',
        'province_id',
        'regency_id',
        'district_id',
        'village_id',
        'address',
        'latitude',
        'longitude',
        'description',
        'bedrooms',
        'bathrooms',
        'floors',
        'area',
        'building_area',
        'facilities',
        'rent_price',
        'status'
    ];

    protected $casts = [
        'area' => 'float',
        'building_area' => 'float',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function photos()
    {
        return $this->hasMany(PropertyPhoto::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function regency()
    {
        return $this->belongsTo(Regency::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function village()
    {
        return $this->belongsTo(Village::class);
    }

    public function facilityItems()
    {
        return $this->belongsToMany(Facility::class, 'property_facility')
            ->withPivot('value')
            ->withTimestamps();
    }

    public function availabilityCycles()
    {
        return $this->hasMany(PropertyAvailabilityCycle::class);
    }

    public function rentalRequests()
    {
        return $this->hasMany(RentalRequest::class);
    }

}
