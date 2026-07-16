<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Gallery extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'email',
        'access_password',
        'device_token_hash',
        'claimed_at',
    ];

    protected $hidden = [
        'access_password',
        'device_token_hash',
    ];

    protected function casts(): array
    {
        return [
            'access_password' => 'hashed',
            'claimed_at' => 'datetime',
        ];
    }

    public function accessCookieName(): string
    {
        return 'gallery_device_'.$this->id;
    }

    public function artworks(): BelongsToMany
    {
        return $this->belongsToMany(Artwork::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
