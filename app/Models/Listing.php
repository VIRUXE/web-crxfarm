<?php

namespace App\Models;

use App\Enums\ListingType;
use App\Enums\PartCategory;
use App\Support\DescriptionCleaner;
use App\Support\TitleNormalizer;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Listing extends Model
{
    use HasFactory;

    protected $fillable = [
        'type', 'title', 'chassis', 'category', 'bolt_pattern', 'price', 'description',
        'missing_parts', 'location', 'status', 'source_marketplace_id', 'thumbnail_image_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ListingType::class,
            'category' => PartCategory::class,
        ];
    }

    protected function title(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null ? TitleNormalizer::normalize($value) : null,
        );
    }

    /**
     * Standard Honda/Acura bolt patterns for filtering and quick selection.
     *
     * @return list<string>
     */
    public static function standardBoltPatterns(): array
    {
        return [
            '4x100',
            '4x114.3',
            '5x114.3',
            '5x120',
        ];
    }

    public function isCar(): bool
    {
        return $this->type === ListingType::Car;
    }

    public function isPart(): bool
    {
        return $this->type === ListingType::Part;
    }

    /**
     * Parse missing parts into an array of individual items.
     *
     * @return list<string>
     */
    public function missingPartsList(): array
    {
        if (empty($this->missing_parts)) {
            return [];
        }

        $raw = trim((string) $this->missing_parts);
        if ($raw === '') {
            return [];
        }

        if (str_contains($raw, "\n") || str_contains($raw, "\r")) {
            $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        } elseif (str_contains($raw, ',') || str_contains($raw, ';')) {
            $lines = preg_split('/[,;]+/', $raw) ?: [];
        } else {
            $lines = [$raw];
        }

        $items = [];
        foreach ($lines as $line) {
            $item = trim(preg_replace('/^[-*•\d\.\)]+\s*/u', '', trim($line)));
            if ($item !== '') {
                $items[] = $item;
            }
        }

        return array_values($items);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ListingImage::class)->orderBy('seq');
    }

    public function thumbnailImage(): BelongsTo
    {
        return $this->belongsTo(ListingImage::class, 'thumbnail_image_id');
    }

    /**
     * The photo to feature in the catalog grid and social/OG previews: the
     * admin-picked thumbnail if one is set, otherwise the first photo.
     */
    public function featuredImage(): ?ListingImage
    {
        return $this->thumbnailImage ?? $this->images->first();
    }

    public function videos(): HasMany
    {
        return $this->hasMany(ListingVideo::class)->orderBy('seq');
    }

    /**
     * Chassis a part fits. Cars use the single `chassis` column instead,
     * since a car listing is always exactly one chassis.
     */
    public function compatibleChassis(): BelongsToMany
    {
        return $this->belongsToMany(Chassis::class, 'listing_chassis');
    }

    /**
     * URL-safe slug for search-engine-friendly URLs.
     */
    public function slug(): string
    {
        return Str::slug((string) $this->title) ?: 'item';
    }

    /**
     * Canonical full URL for this listing.
     */
    public function url(): string
    {
        $key = $this->getKey() ?? $this->id ?? 1;

        return route('catalog.show', ['listing' => $key, 'slug' => $this->slug()]);
    }

    public function brandName(): string
    {
        return DescriptionCleaner::brandName((string) $this->title);
    }

    public function seoMetaDescription(): string
    {
        return DescriptionCleaner::seoMetaDescription($this);
    }

    /**
     * @return array<string, mixed>
     */
    public function schemaJsonLd(): array
    {
        return DescriptionCleaner::schemaJsonLd($this);
    }

    public function cleanedDescription(): ?string
    {
        return DescriptionCleaner::clean($this->description);
    }

    /**
     * Human-readable chassis value regardless of listing type.
     */
    public function chassisLabel(): string
    {
        if ($this->isCar()) {
            return (string) $this->chassis;
        }

        return $this->compatibleChassis->pluck('name')->implode(', ');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
                ->orWhere('chassis', 'like', "%{$term}%")
                ->orWhere('bolt_pattern', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhereHas('compatibleChassis', fn ($cq) => $cq->where('name', 'like', "%{$term}%"));
        });
    }

    public function scopeChassis($query, ?string $chassis)
    {
        if (! $chassis) {
            return $query;
        }

        return $query->where(function ($q) use ($chassis) {
            $q->where('chassis', $chassis)
                ->orWhereHas('compatibleChassis', fn ($cq) => $cq->where('name', $chassis));
        });
    }

    public function scopeBoltPattern($query, ?string $boltPattern)
    {
        if (! $boltPattern) {
            return $query;
        }

        return $query->where('bolt_pattern', 'like', "%{$boltPattern}%");
    }

    public function scopeCategory($query, PartCategory|string|null $category)
    {
        if (! $category) {
            return $query;
        }

        $value = $category instanceof PartCategory ? $category->value : $category;

        return $query->where('category', $value);
    }

    public function scopeCategoryTag($query, PartCategory|string|null $category, ?string $tagKey)
    {
        if (! $tagKey || ! $category) {
            return $query;
        }

        $catEnum = $category instanceof PartCategory ? $category : PartCategory::tryFrom((string) $category);
        if (! $catEnum) {
            return $query;
        }

        $tags = $catEnum->tags();
        if (! isset($tags[$tagKey])) {
            return $query;
        }

        $keywords = $tags[$tagKey]['keywords'] ?? [];
        if (empty($keywords)) {
            return $query;
        }

        return $query->where(function ($q) use ($keywords) {
            foreach ($keywords as $kw) {
                $q->orWhere('title', 'like', "%{$kw}%");
            }
        });
    }

    /**
     * Resolve all matching category or vehicle style tags for this listing.
     *
     * @return array<string, string> Map of tag_key => tag_label
     */
    public function tags(): array
    {
        $matches = [];
        $text = trim($this->title.' '.($this->description ?? ''));

        if ($this->isPart() && $this->category) {
            $catTags = $this->category->tags();
            foreach ($catTags as $key => $data) {
                foreach ($data['keywords'] as $kw) {
                    if (stripos($text, $kw) !== false) {
                        $matches[$key] = $data['label'];
                        break;
                    }
                }
            }
        } elseif ($this->isCar()) {
            $carTags = self::carTags();
            foreach ($carTags as $key => $data) {
                foreach ($data['keywords'] as $kw) {
                    if (stripos($this->title, $kw) !== false) {
                        $matches[$key] = $data['label'];
                        break;
                    }
                }
            }
        }

        return $matches;
    }

    /**
     * @return array<string, array{label: string, keywords: list<string>}>
     */
    public static function carTags(): array
    {
        return [
            'hatchbacks' => ['label' => 'Hatchbacks', 'keywords' => ['hatch', 'hatchback', '3d', '2d hatch']],
            'coupes' => ['label' => 'Coupes', 'keywords' => ['coupe', '2d coupe']],
            'sedans' => ['label' => 'Sedans', 'keywords' => ['sedan', '4d', '4 door']],
            'wagons' => ['label' => 'Wagons', 'keywords' => ['wagon', 'wagovan']],
            'shells_rollers' => ['label' => 'Shells & Rollers', 'keywords' => ['shell', 'roller', 'racing shell']],
            'part_outs' => ['label' => 'Part-Outs', 'keywords' => ['part out', 'parting out', 'parts car', 'for parts']],
        ];
    }

    public function scopeCarTag($query, ?string $tagKey)
    {
        if (! $tagKey) {
            return $query;
        }

        $tags = self::carTags();
        if (! isset($tags[$tagKey])) {
            return $query;
        }

        $keywords = $tags[$tagKey]['keywords'] ?? [];
        if (empty($keywords)) {
            return $query;
        }

        return $query->where(function ($q) use ($keywords) {
            foreach ($keywords as $kw) {
                $q->orWhere('title', 'like', "%{$kw}%");
            }
        });
    }

    public function scopeType($query, ListingType|string|null $type)
    {
        if (! $type) {
            return $query;
        }

        $value = $type instanceof ListingType ? $type->value : $type;

        return $query->where('type', $value);
    }

    /**
     * @return list<string>
     */
    public static function chassisSuggestions(): array
    {
        $defaults = [
            'CRX',
            'EF',
            'EG',
            'EK',
            'Del Sol',
            'DA Integra',
            'DC2 Integra',
            'Accord',
            'CR-V',
            'Prelude',
            'S2000',
            'Civic Wagon',
            'Fit',
        ];

        $existing = static::query()
            ->whereNotNull('chassis')
            ->where('chassis', '!=', '')
            ->distinct()
            ->orderBy('chassis')
            ->pluck('chassis')
            ->all();

        $chassisTable = Chassis::query()->orderBy('name')->pluck('name')->all();

        return array_values(array_unique(array_merge($defaults, $existing, $chassisTable)));
    }

    /**
     * @return list<string>
     */
    public static function partSuggestions(): array
    {
        $defaults = [
            'Hood',
            'Front Bumper',
            'Rear Bumper',
            'Driver Seat',
            'Passenger Seat',
            'Rear Seats',
            'ECU',
            'Engine (Long Block)',
            'Transmission',
            'Wiring Harness',
            'Headlights',
            'Corner Lights',
            'Tail Lights',
            'Gauge Cluster',
            'Sunroof Assembly',
            'Sunroof Panel',
            'Steering Wheel',
            'Center Console',
            'Shift Linkage',
            'Dashboard Assembly',
            'Door (Driver)',
            'Door (Passenger)',
            'Door Panels',
            'Fender (Driver)',
            'Fender (Passenger)',
            'Hatch / Tailgate',
            'Side Mirrors',
            'Radiator & Fan',
            'Distributor',
            'Alternator',
            'Starter Motor',
            'AC Compressor',
            'Power Steering Pump',
            'Brake Calipers',
            'Front Suspension',
            'Rear Trailing Arms',
            'Exhaust Manifold',
            'Catalytic Converter',
            'Wheels / Rims',
            'Fuel Pump & Tank',
        ];

        $partTitles = static::query()
            ->where('type', ListingType::Part)
            ->whereNotNull('title')
            ->where('title', '!=', '')
            ->distinct()
            ->pluck('title')
            ->all();

        $missingPartsDb = static::query()
            ->where('type', ListingType::Car)
            ->whereNotNull('missing_parts')
            ->where('missing_parts', '!=', '')
            ->pluck('missing_parts')
            ->all();

        $fromMissing = [];
        foreach ($missingPartsDb as $raw) {
            $parsed = (new static(['missing_parts' => $raw]))->missingPartsList();
            foreach ($parsed as $item) {
                if ($item !== '') {
                    $fromMissing[] = $item;
                }
            }
        }

        $all = array_merge($defaults, $partTitles, $fromMissing);

        return array_values(array_unique($all));
    }
}
