<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Listing extends Model
{
    use HasFactory;

    protected $fillable = [
        'type', 'title', 'chassis', 'price', 'description',
        'missing_parts', 'location', 'status', 'source_marketplace_id',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(ListingImage::class)->orderBy('seq');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
                ->orWhere('chassis', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    public function scopeChassis($query, ?string $chassis)
    {
        return $chassis ? $query->where('chassis', $chassis) : $query;
    }
}
