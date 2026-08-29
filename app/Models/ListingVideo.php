<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ListingVideo extends Model
{
    protected $fillable = ['listing_id', 'path', 'poster_path', 'seq'];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path).'?v='.($this->updated_at?->timestamp ?? 0);
    }

    public function getPosterUrlAttribute(): ?string
    {
        if (! $this->poster_path) {
            return null;
        }

        return Storage::disk('public')->url($this->poster_path).'?v='.($this->updated_at?->timestamp ?? 0);
    }
}
