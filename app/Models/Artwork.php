<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Artwork extends Model
{
    protected $fillable = [
        'title',
        'is_published',
        'background_color',
    ];

    public function images()
    {
        return $this->hasMany(ArtworkImage::class)->orderBy('position');
    }
}