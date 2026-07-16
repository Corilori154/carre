<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneratedComposition extends Model
{
    protected $fillable = [
        'artwork_id',
        'fingerprint',
        'composition',
    ];

    protected function casts(): array
    {
        return [
            'composition' => 'array',
        ];
    }
}
