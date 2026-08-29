<?php

namespace Tests\Feature;

use App\Models\Listing;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogListingImageTest extends TestCase
{
    public function test_catalog_grid_uses_stored_file_url_for_listing_photo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('listings/photo.jpg', 'fake-image');

        $listing = Listing::factory()->create([
            'title' => 'CRX Hood',
            'status' => 'available',
        ]);
        $listing->images()->create([
            'path' => 'listings/photo.jpg',
            'seq' => 0,
        ]);

        $url = Storage::disk('public')->url('listings/photo.jpg');

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee('CRX Hood')
            ->assertSee($url)
            ->assertDontSee('No photo yet');
    }

    public function test_listing_page_renders_stored_file_as_image_tag(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('listings/photo.jpg', 'fake-image');

        $listing = Listing::factory()->create([
            'title' => 'CRX Hood',
            'status' => 'available',
        ]);
        $listing->images()->create([
            'path' => 'listings/photo.jpg',
            'seq' => 0,
        ]);

        $url = Storage::disk('public')->url('listings/photo.jpg');

        $this->get(route('catalog.show', $listing))
            ->assertOk()
            ->assertSee('<img', false)
            ->assertSee($url)
            ->assertDontSee('No photos yet');
    }
}
