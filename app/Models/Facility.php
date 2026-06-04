<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'category',
        'is_active',
    ];

    public function properties()
    {
        return $this->belongsToMany(Property::class, 'property_facility')
            ->withPivot('value')
            ->withTimestamps();
    }
}
