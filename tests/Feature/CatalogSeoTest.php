<?php

namespace Tests\Feature;

use App\Enums\ListingType;
use App\Enums\PartCategory;
use App\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_returns_valid_xml(): void
    {
        $listing = Listing::factory()->create([
            'title' => '1990 Honda Civic Si Gauge Cluster',
            'status' => 'available',
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=utf-8')
            ->assertSee('<urlset', false)
            ->assertSee($listing->url(), false)
            ->assertSee('<priority>0.9</priority>', false);
    }

    public function test_listing_page_renders_schema_org_json_ld(): void
    {
        $listing = Listing::factory()->create([
            'type' => ListingType::Part,
            'title' => 'Skunk2 Racing Pro Series Cam Gear Set',
            'category' => PartCategory::EngineDrivetrain,
            'price' => '$260',
            'status' => 'available',
            'description' => 'Titanium cam gears for H22A',
        ]);

        $response = $this->get($listing->url());

        $response->assertOk()
            ->assertSee('application/ld+json', false)
            ->assertSee('"@type": "Product"', false)
            ->assertSee('"name": "Skunk2"', false)
            ->assertSee('"price": 260', false)
            ->assertSee('"@type": "BreadcrumbList"', false);
    }

    public function test_listing_slug_redirects_mismatched_slugs(): void
    {
        $listing = Listing::factory()->create([
            'title' => '1995 Honda Del Sol Targa Seals',
            'status' => 'available',
        ]);

        $response = $this->get('/listing/'.$listing->id.'/wrong-slug');

        $response->assertRedirect($listing->url());
    }

    public function test_clean_descriptions_command_updates_database(): void
    {
        $listing = Listing::factory()->create([
            'title' => 'Honda B18 Distributor',
            'description' => "Good OEM distributor\n[hidden information]\npm me for faster response\ncrx farm",
            'status' => 'available',
        ]);

        $this->artisan('listings:clean-descriptions')
            ->assertSuccessful();

        $listing->refresh();

        $this->assertSame('Good OEM distributor', $listing->description);
    }
}
