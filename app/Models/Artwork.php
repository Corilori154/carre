<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Artwork extends Model
{
    protected $fillable = [
        'title',
        'is_published',
    ];

    public function images()
    {
        return $this->hasMany(ArtworkImage::class)->orderBy('position');
    }
}