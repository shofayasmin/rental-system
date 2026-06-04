<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Property;
use App\Models\User;

class Transaction extends Model
{
    protected $fillable = [
        'rental_request_id',
        'property_id',
        'tenant_id',
        'agent_id',
        'contract_extension_id',
        'amount',
        'type',
        'status'
    ];

    public function rentalRequest()
    {
        return $this->belongsTo(RentalRequest::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function contract()
    {
        return $this->hasOneThrough(Contract::class, RentalRequest::class, 'id', 'rental_request_id', 'rental_request_id', 'id');
    }

    public function contractExtension()
    {
        return $this->belongsTo(ContractExtension::class, 'contract_extension_id');
    }


}
