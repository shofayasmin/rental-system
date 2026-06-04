<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'property_id',
        'rental_request_id',
        'message',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function rentalRequest()
    {
        return $this->belongsTo(RentalRequest::class);
    }
}
