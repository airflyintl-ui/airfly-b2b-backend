<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupFareOffer extends Model
{
    use HasFactory;

    protected $table = 'group_fare_offers';

    protected $fillable = [
        'airline_name',
        'origin',
        'destination',
        'departure_date',
        'return_date',
        'fare',
        'currency',
        'total_seats',
        'available_seats',
        'description',
        'terms_conditions',
        'status',
    ];

    protected $casts = [
        'departure_date' => 'date',
        'return_date' => 'date',
        'fare' => 'decimal:2',
        'total_seats' => 'integer',
        'available_seats' => 'integer',
    ];

    public function requests(): HasMany
    {
        return $this->hasMany(GroupFareRequest::class);
    }
}
