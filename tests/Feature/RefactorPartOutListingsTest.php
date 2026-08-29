<?php

namespace Tests\Feature;

use App\Enums\ListingType;
use App\Enums\PartCategory;
use App\Models\Chassis;
use App\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefactorPartOutListingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_refactors_placeholder_prices_and_reclassifies_part_outs(): void
    {
        $chassisDelSol = Chassis::firstOrCreate(['name' => 'Del Sol']);

        // Listing with $123 price that is a car part out
        $delSolCar = Listing::factory()->create([
            'type' => ListingType::Part,
            'title' => '1995 Honda del sol',
            'description' => 'Full professional part out. Skipped title. Runs and drives. Crx farm',
            'price' => '$123',
            'category' => PartCategory::Other,
        ]);
        $delSolCar->compatibleChassis()->sync([$chassisDelSol->id]);

        // Multi-part lot with $123 price that remains a part
        $rimsPart = Listing::factory()->create([
            'type' => ListingType::Part,
            'title' => 'Honda rims 4x100',
            'description' => 'Multiple sets of rims. 650 white rotas, 550 hx. Crx farm',
            'price' => '$123',
            'category' => PartCategory::WheelsTires,
        ]);

        // Real priced item
        $realListing = Listing::factory()->create([
            'type' => ListingType::Part,
            'title' => 'CRX Sunroof Panel',
            'price' => '$450',
            'category' => PartCategory::ExteriorBody,
        ]);

        $migration = require database_path('migrations/2026_08_29_210000_refactor_part_out_listings_and_placeholder_prices.php');
        $migration->up();

        $delSolCar->refresh();
        $this->assertSame(ListingType::Car, $delSolCar->type);
        $this->assertNull($delSolCar->category);
        $this->assertSame('Del Sol', $delSolCar->chassis);
        $this->assertNull($delSolCar->price);
        $this->assertEmpty($delSolCar->compatibleChassis);

        $rimsPart->refresh();
        $this->assertSame(ListingType::Part, $rimsPart->type);
        $this->assertSame(PartCategory::WheelsTires, $rimsPart->category);
        $this->assertNull($rimsPart->price);

        $realListing->refresh();
        $this->assertSame('$450', $realListing->price);
    }
}
