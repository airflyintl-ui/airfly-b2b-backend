<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisaInformation extends Model
{
    use HasFactory;

    protected $table = 'visa_informations';

    protected $fillable = [
        'destination_country',
        'visa_type',
        'processing_time',
        'validity',
        'entry_type',
        'visa_fee',
        'currency',
        'required_documents',
        'description',
        'terms_conditions',
        'is_active',
    ];

    protected $casts = [
        'required_documents' => 'array',
        'visa_fee' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
