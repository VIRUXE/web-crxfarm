<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ListingImage extends Model
{
    use HasFactory;

    protected $fillable = ['listing_id', 'path', 'og_path', 'seq'];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function getUrlAttribute(): string
    {
        // Re-imports overwrite the same file path, so bust the browser cache
        // with the row's updated_at; the URL changes whenever the photo does.
        $version = $this->updated_at?->timestamp ?? 0;

        return Storage::disk('public')->url($this->path).'?v='.$version;
    }

    /**
     * The 1200x630 cover-cropped variant for og:image/twitter:image, falling
     * back to the regular photo for any row created before og_path existed.
     */
    public function getOgUrlAttribute(): string
    {
        $version = $this->updated_at?->timestamp ?? 0;
        $path = $this->og_path ?? $this->path;

        return Storage::disk('public')->url($path).'?v='.$version;
    }
}
