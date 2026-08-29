<?php

namespace Tests\Feature;

use App\Enums\ListingType;
use App\Enums\PartCategory;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminListingCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_listing_with_category(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);

        $response = $this->actingAs($user)->post(route('admin.listings.store'), [
            'type' => 'part',
            'title' => 'Del Sol Cluster Manual',
            'chassis' => 'Del Sol',
            'category' => PartCategory::Interior->value,
            'price' => '$160',
            'description' => 'Original manual cluster',
            'status' => 'available',
        ]);

        $listing = Listing::first();
        $this->assertNotNull($listing);
        $this->assertSame('Del Sol Cluster Manual', $listing->title);
        $this->assertSame(PartCategory::Interior, $listing->category);
        $response->assertRedirect(route('admin.listings.edit', $listing));
    }

    public function test_admin_can_update_listing_category(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $listing = Listing::factory()->create([
            'category' => PartCategory::Interior,
        ]);

        $this->actingAs($user)->put(route('admin.listings.update', $listing), [
            'type' => 'part',
            'title' => $listing->title,
            'chassis' => $listing->chassis,
            'category' => PartCategory::LightingElectrical->value,
            'price' => '$150',
            'status' => 'available',
        ])->assertRedirect(route('admin.listings.edit', $listing));

        $listing->refresh();
        $this->assertSame(PartCategory::LightingElectrical, $listing->category);
    }

    public function test_admin_can_clear_category_to_null(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $listing = Listing::factory()->create([
            'category' => PartCategory::Interior,
        ]);

        $this->actingAs($user)->put(route('admin.listings.update', $listing), [
            'type' => 'part',
            'title' => $listing->title,
            'chassis' => $listing->chassis,
            'category' => null,
            'price' => '$150',
            'status' => 'available',
        ])->assertRedirect(route('admin.listings.edit', $listing));

        $listing->refresh();
        $this->assertNull($listing->category);
    }

    public function test_invalid_category_fails_validation(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);

        $response = $this->actingAs($user)->post(route('admin.listings.store'), [
            'type' => 'part',
            'title' => 'Invalid Category Part',
            'category' => 'non_existent_category_slug',
            'status' => 'available',
        ]);

        $response->assertSessionHasErrors('category');
    }

    public function test_invalid_type_fails_validation(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);

        $response = $this->actingAs($user)->post(route('admin.listings.store'), [
            'type' => 'motorcycle',
            'title' => 'Invalid Type Item',
            'status' => 'available',
        ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_admin_can_create_car_listing_with_listing_type_enum(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);

        $response = $this->actingAs($user)->post(route('admin.listings.store'), [
            'type' => ListingType::Car->value,
            'title' => '1991 CRX Si Shell',
            'chassis' => 'CRX',
            'status' => 'available',
            'missing_parts' => 'Engine and seats pulled',
        ]);

        $listing = Listing::first();
        $this->assertNotNull($listing);
        $this->assertTrue($listing->isCar());
        $this->assertSame(ListingType::Car, $listing->type);
        $response->assertRedirect(route('admin.listings.edit', $listing));
    }

    public function test_admin_form_renders_chassis_datalist_and_quick_picks(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);

        $response = $this->actingAs($user)->get(route('admin.listings.create'));

        $response->assertOk()
            ->assertSee('id="chassis-suggestions"', false)
            ->assertSee('list="chassis-suggestions"', false)
            ->assertSee('Quick pick:');
    }

    public function test_listing_chassis_suggestions_includes_defaults_and_database_values(): void
    {
        Listing::factory()->create(['chassis' => 'CustomChassis123']);

        $suggestions = Listing::chassisSuggestions();

        $this->assertContains('CRX', $suggestions);
        $this->assertContains('Del Sol', $suggestions);
        $this->assertContains('CustomChassis123', $suggestions);
    }

    public function test_admin_can_create_car_listing_with_missing_parts_items_array(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);

        $response = $this->actingAs($user)->post(route('admin.listings.store'), [
            'type' => ListingType::Car->value,
            'title' => '1990 Civic Si Shell',
            'chassis' => 'EF',
            'status' => 'available',
            'missing_parts' => ['Hood', 'Front bumper', 'Driver seat', ''],
        ]);

        $listing = Listing::first();
        $this->assertNotNull($listing);
        $this->assertSame("Hood\nFront bumper\nDriver seat", $listing->missing_parts);
        $this->assertSame(['Hood', 'Front bumper', 'Driver seat'], $listing->missingPartsList());
        $response->assertRedirect(route('admin.listings.edit', $listing));
    }

    public function test_admin_form_renders_missing_parts_as_individual_item_inputs(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $listing = Listing::factory()->car()->create([
            'missing_parts' => "Hood\nFront bumper\nDriver seat",
        ]);

        $response = $this->actingAs($user)->get(route('admin.listings.edit', $listing));

        $response->assertOk()
            ->assertSee('name="missing_parts[]"', false)
            ->assertSee('list="part-suggestions"', false)
            ->assertSee('value="Hood"', false)
            ->assertSee('value="Front bumper"', false)
            ->assertSee('value="Driver seat"', false)
            ->assertSee('Add part')
            ->assertSee('Quick add:');
    }

    public function test_admin_form_renders_part_suggestions_datalist(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);

        $response = $this->actingAs($user)->get(route('admin.listings.create'));

        $response->assertOk()
            ->assertSee('id="part-suggestions"', false)
            ->assertSee('<option value="Hood"></option>', false)
            ->assertSee('<option value="ECU"></option>', false)
            ->assertSee('<option value="Front Bumper"></option>', false);
    }

    public function test_listing_part_suggestions_includes_defaults_and_database_values(): void
    {
        Listing::factory()->create([
            'type' => ListingType::Part,
            'title' => 'Custom B18 Engine Swap',
        ]);

        $suggestions = Listing::partSuggestions();

        $this->assertContains('Hood', $suggestions);
        $this->assertContains('Driver Seat', $suggestions);
        $this->assertContains('ECU', $suggestions);
        $this->assertContains('Custom B18 Engine Swap', $suggestions);
    }
}
