<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Artwork extends Model
{
    protected $fillable = [
        'title',
        'is_public',
        'background_color',
    ];

    public function images()
    {
        return $this->hasMany(ArtworkImage::class)->orderBy('position');
    }

    public function getGeneratedCountAttribute(): int
    {
        $interval = (int) SettingTime::getValue('shuffle_interval_seconds', 10);

        if ($interval <= 0 || !$this->created_at) {
            return 0;
        }

        $secondsSinceCreation = now()->timestamp - $this->created_at->timestamp;

        if ($secondsSinceCreation < 0) {
            return 0;
        }

        return (int) floor($secondsSinceCreation / $interval);
    }
}