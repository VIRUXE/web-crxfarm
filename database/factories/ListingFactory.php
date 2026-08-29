<?php

namespace Database\Factories;

use App\Enums\ListingType;
use App\Enums\PartCategory;
use App\Models\Listing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Listing>
 */
class ListingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => ListingType::Part,
            'title' => fake()->words(4, true),
            'chassis' => 'CRX',
            'category' => fake()->randomElement(PartCategory::cases()),
            'price' => '$100',
            'description' => fake()->sentence(),
            'missing_parts' => null,
            'location' => 'Rossville, KS',
            'status' => 'available',
            'source_marketplace_id' => null,
        ];
    }

    public function car(): static
    {
        return $this->state(fn () => [
            'type' => ListingType::Car,
            'category' => null,
            'missing_parts' => "Hood\nDriver seat",
        ]);
    }

    public function part(): static
    {
        return $this->state(fn () => [
            'type' => ListingType::Part,
            'missing_parts' => null,
        ]);
    }
}
