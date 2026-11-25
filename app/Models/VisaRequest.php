<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisaRequest extends Model
{
    use HasFactory;

    protected $table = 'visa_requests';

    protected $fillable = [
        'agent_id',
        'visa_information_id',
        'destination_country',
        'visa_type',
        'passenger_name',
        'passport_number',
        'email',
        'mobile',
        'remarks',
        'documents',
        'visa_fee',
        'currency',
        'status',
        'admin_note',
        'reviewed_at',
    ];

    protected $casts = [
        'documents' => 'array',
        'visa_fee' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];
}
