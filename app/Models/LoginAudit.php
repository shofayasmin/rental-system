<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class LoginAudit extends Model
{
    protected $table = 'login_audit';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'logged_in_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    
}
