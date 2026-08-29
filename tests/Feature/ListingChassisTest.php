<?php

namespace Tests\Feature;

use App\Enums\ListingType;
use App\Models\Chassis;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingChassisTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_part_with_multiple_chassis(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $ef = Chassis::create(['name' => 'EF']);
        $eg = Chassis::create(['name' => 'EG']);

        $response = $this->actingAs($user)->post(route('admin.listings.store'), [
            'type' => ListingType::Part->value,
            'title' => 'OEM Front Bumper',
            'chassis_ids' => [$ef->id, $eg->id],
            'chassis_other' => 'Del Sol, EK',
            'status' => 'available',
        ]);

        $listing = Listing::first();
        $this->assertNotNull($listing);
        $response->assertRedirect(route('admin.listings.edit', $listing));

        $names = $listing->compatibleChassis()->pluck('name')->sort()->values()->all();
        $this->assertSame(['Del Sol', 'EF', 'EG', 'EK'], $names);
    }

    public function test_admin_can_update_a_parts_compatible_chassis(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $ef = Chassis::create(['name' => 'EF']);
        $eg = Chassis::create(['name' => 'EG']);
        $listing = Listing::factory()->part()->create();
        $listing->compatibleChassis()->attach($ef);

        $this->actingAs($user)->put(route('admin.listings.update', $listing), [
            'type' => ListingType::Part->value,
            'title' => $listing->title,
            'chassis_ids' => [$eg->id],
            'status' => 'available',
        ])->assertRedirect(route('admin.listings.edit', $listing));

        $names = $listing->compatibleChassis()->pluck('name')->all();
        $this->assertSame(['EG'], $names);
    }

    public function test_a_part_with_multiple_chassis_shows_up_when_filtering_by_any_of_them(): void
    {
        $ef = Chassis::create(['name' => 'EF']);
        $eg = Chassis::create(['name' => 'EG']);
        $listing = Listing::factory()->part()->create(['title' => 'OZ Rims', 'status' => 'available']);
        $listing->compatibleChassis()->attach([$ef->id, $eg->id]);

        $response = $this->get(route('catalog.index', ['chassis' => 'EG']));

        $response->assertOk()->assertSee('OZ Rims');
    }

    public function test_listing_chassis_label_lists_all_compatible_chassis_for_a_part(): void
    {
        $ef = Chassis::create(['name' => 'EF']);
        $eg = Chassis::create(['name' => 'EG']);
        $listing = Listing::factory()->part()->create();
        $listing->compatibleChassis()->attach([$ef->id, $eg->id]);

        $this->assertSame('EF, EG', $listing->chassisLabel());
    }

    public function test_listing_chassis_label_uses_the_chassis_column_for_cars(): void
    {
        $listing = Listing::factory()->car()->create(['chassis' => 'CRX']);

        $this->assertSame('CRX', $listing->chassisLabel());
    }
}
