<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\PartCategory;
use App\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_controls_target_the_canonical_catalog_url(): void
    {
        $response = $this->get('http://localhost/');

        $response->assertSee('action="http://localhost/"', false);
        $response->assertSee('hx-get="http://localhost/"', false);
    }

    public function test_catalog_index_presents_jeremiah_as_the_contact(): void
    {
        $response = $this->get(route('catalog.index'));

        $response
            ->assertSee('Message Jeremiah if you do not see what you need.')
            ->assertSee('Jeremiah ships parts across the United States and internationally.')
            ->assertDontSee('Message us')
            ->assertDontSee('We ship');
    }

    public function test_listing_page_presents_jeremiah_as_the_contact(): void
    {
        $listing = Listing::factory()->create();

        $response = $this->get(route('catalog.show', $listing));

        $response
            ->assertSee('Message on Facebook Messenger')
            ->assertSee('https://m.me/jeremiah.freeman.116318')
            ->assertDontSee('Message us');
    }

    public function test_search_request_returns_only_matching_available_listings(): void
    {
        Listing::factory()->create([
            'title' => 'B18 Engine Mount',
            'status' => 'available',
        ]);
        Listing::factory()->create([
            'title' => 'CRX Tail Light',
            'status' => 'available',
        ]);
        Listing::factory()->create([
            'title' => 'B18 Engine Block',
            'status' => 'sold',
        ]);

        $response = $this->get('http://localhost/?q=engine', ['HX-Request' => 'true']);

        $response
            ->assertViewIs('catalog.partials.grid')
            ->assertSee('B18 Engine Mount')
            ->assertDontSee('CRX Tail Light')
            ->assertDontSee('B18 Engine Block');
    }

    public function test_category_filter_returns_only_matching_available_listings(): void
    {
        Listing::factory()->create([
            'title' => 'Del Sol Cluster Manual',
            'category' => PartCategory::Interior,
            'status' => 'available',
        ]);
        Listing::factory()->create([
            'title' => 'CRX Sunroof Panel',
            'category' => PartCategory::ExteriorBody,
            'status' => 'available',
        ]);
        Listing::factory()->create([
            'title' => 'CRX Si Driver Seat',
            'category' => PartCategory::Interior,
            'status' => 'sold',
        ]);

        $response = $this->get(route('catalog.index', ['category' => PartCategory::Interior->value]));

        $response
            ->assertOk()
            ->assertSee('Del Sol Cluster Manual')
            ->assertDontSee('CRX Sunroof Panel')
            ->assertDontSee('CRX Si Driver Seat');
    }

    public function test_catalog_index_renders_category_filter_pills(): void
    {
        $response = $this->get(route('catalog.index'));

        $response->assertOk()
            ->assertSee('All categories')
            ->assertSee('Engine &amp; Drivetrain', false)
            ->assertSee('Interior')
            ->assertSee('Exterior &amp; Body', false)
            ->assertSee('Lighting &amp; Electrical', false)
            ->assertSee('Suspension, Brakes &amp; Wheels', false);
    }

    public function test_listing_show_page_displays_category_badge(): void
    {
        $listing = Listing::factory()->create([
            'title' => 'Del Sol Cluster Manual',
            'category' => PartCategory::Interior,
            'status' => 'available',
        ]);

        $response = $this->get(route('catalog.show', $listing));

        $response->assertOk()
            ->assertSee('Interior');
    }

    public function test_car_listing_displays_missing_parts_as_individual_items(): void
    {
        $listing = Listing::factory()->car()->create([
            'title' => '1988 Honda CRX Si - parts car',
            'missing_parts' => "Hood\nFront bumper\nDriver seat\nECU",
        ]);

        $response = $this->get(route('catalog.show', $listing));

        $response->assertOk()
            ->assertSee('Already pulled / missing from this car')
            ->assertSee('Hood')
            ->assertSee('Front bumper')
            ->assertSee('Driver seat')
            ->assertSee('ECU')
            ->assertSee('Everything else is likely still on the car. Ask and Jeremiah will confirm.');
    }

    public function test_part_listing_does_not_display_missing_parts_section(): void
    {
        $listing = Listing::factory()->part()->create([
            'title' => 'CRX Sunroof Panel',
        ]);

        $response = $this->get(route('catalog.show', $listing));

        $response->assertOk()
            ->assertDontSee('Already pulled / missing from this car');
    }
}
