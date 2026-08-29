<?php

use App\Enums\ListingType;
use App\Models\Chassis;
use App\Models\Listing;
use App\Support\ListingClassifier;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $listings = Listing::with('compatibleChassis')->get();

        foreach ($listings as $listing) {
            $isPlaceholder = ListingClassifier::isPlaceholderPrice($listing->price);
            $classified = ListingClassifier::classify($listing->title, $listing->description, $listing->price);

            $updates = [];

            if ($isPlaceholder || $listing->price === '$123' || $listing->price === '$1,234') {
                $updates['price'] = null;
            }

            if ($classified['type'] === ListingType::Car) {
                $updates['type'] = ListingType::Car;
                $updates['category'] = null;
                $updates['chassis'] = $classified['chassis'][0] ?? null;
                if ($listing->compatibleChassis->isNotEmpty()) {
                    $listing->compatibleChassis()->detach();
                }
            } elseif ($classified['type'] === ListingType::Part) {
                if (! empty($classified['chassis']) && $listing->compatibleChassis->isEmpty()) {
                    $chassisIds = array_map(
                        fn (string $name) => Chassis::firstOrCreate(['name' => $name])->id,
                        $classified['chassis']
                    );
                    $listing->compatibleChassis()->sync($chassisIds);
                }
            }

            if (! empty($updates)) {
                $listing->update($updates);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data cleanup migration; down is non-destructive
    }
};
