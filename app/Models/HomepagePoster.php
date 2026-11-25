<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomepagePoster extends Model
{
    use HasFactory;

    protected $table = 'homepage_posters';

    protected $fillable = [
        'title',
        'subtitle',
        'description',
        'image_url',
        'cloudinary_public_id',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
