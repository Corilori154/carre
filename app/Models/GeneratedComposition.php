<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedComposition extends Model
{
    protected $fillable = [
        'artwork_id',
        'gallery_id',
        'first_name',
        'last_name',
        'email',
        'fingerprint',
        'composition',
    ];

    public function artwork(): BelongsTo
    {
        return $this->belongsTo(Artwork::class);
    }

    public function gallery(): BelongsTo
    {
        return $this->belongsTo(Gallery::class);
    }

    protected function casts(): array
    {
        return [
            'composition' => 'array',
        ];
    }
}
