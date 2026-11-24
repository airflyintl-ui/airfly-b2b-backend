<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupFareRequest extends Model
{
    use HasFactory;

    protected $table = 'group_fare_requests';

    protected $fillable = [
        'group_fare_offer_id',
        'agent_id',
        'passenger_count',
        'contact_person',
        'contact_number',
        'contact_email',
        'document_path',
        'status',
        'admin_note',
    ];

    protected $casts = [
        'passenger_count' => 'integer',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(
            GroupFareOffer::class,
            'group_fare_offer_id'
        );
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
