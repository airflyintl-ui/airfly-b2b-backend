<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Agent extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'agents';

    protected $fillable = [
        'agency_name',
        'owner_name',
        'email',
        'phone',
        'address',
        'trade_license',
        'password',
        'wallet',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'wallet' => 'decimal:2',
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function recharges()
    {
        return $this->hasMany(Recharge::class);
    }

    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
