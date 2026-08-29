<?php

namespace App\Models;

use App\Enums\ListingType;
use App\Enums\PartCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Listing extends Model
{
    use HasFactory;

    protected $fillable = [
        'type', 'title', 'chassis', 'category', 'price', 'description',
        'missing_parts', 'location', 'status', 'source_marketplace_id',
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

    /**
     * Chassis a part fits. Cars use the single `chassis` column instead,
     * since a car listing is always exactly one chassis.
     */
    public function compatibleChassis(): BelongsToMany
    {
        return $this->belongsToMany(Chassis::class, 'listing_chassis');
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

    public function scopeCategory($query, PartCategory|string|null $category)
    {
        if (! $category) {
            return $query;
        }

        $value = $category instanceof PartCategory ? $category->value : $category;

        return $query->where('category', $value);
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
