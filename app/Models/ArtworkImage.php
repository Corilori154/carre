<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArtworkImage extends Model
{
    protected $fillable = [
        'artwork_id',
        'image_path',
        'position',
        'composition_position',
        'rotation',
    ];

    public function artwork()
    {
        return $this->belongsTo(Artwork::class);
    }
}