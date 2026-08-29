<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use Tests\TestCase;

class AdminListingSearchTest extends TestCase
{
    public function test_guest_cannot_view_the_listings_index(): void
    {
        $this->get(route('admin.listings.index'))->assertRedirect();
    }

    public function test_listings_index_renders_the_search_form(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);

        $response = $this->actingAs($user)->get(route('admin.listings.index'));

        $response
            ->assertOk()
            ->assertSee('name="q"', false)
            ->assertSee('aria-label="Search listings"', false)
            ->assertSee('hx-target="#listing-results"', false);
    }

    public function test_search_request_returns_matching_listings_including_sold(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);

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

        $response = $this->actingAs($user)->get(route('admin.listings.index', ['q' => 'engine']));

        $response
            ->assertOk()
            ->assertSee('B18 Engine Mount')
            ->assertSee('B18 Engine Block')
            ->assertDontSee('CRX Tail Light')
            ->assertSee('value="engine"', false);
    }

    public function test_empty_search_shows_all_listings(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);

        Listing::factory()->create(['title' => 'B18 Engine Mount']);
        Listing::factory()->create(['title' => 'CRX Tail Light']);

        $response = $this->actingAs($user)->get(route('admin.listings.index'));

        $response
            ->assertOk()
            ->assertSee('B18 Engine Mount')
            ->assertSee('CRX Tail Light');
    }

    public function test_unmatched_search_renders_empty_state(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);

        Listing::factory()->create(['title' => 'CRX Tail Light']);

        $response = $this->actingAs($user)->get(route('admin.listings.index', ['q' => 'engine']));

        $response
            ->assertOk()
            ->assertSee('Nothing matches that search yet.')
            ->assertDontSee('CRX Tail Light');
    }

    public function test_htmx_search_request_returns_the_table_partial(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);

        Listing::factory()->create(['title' => 'B18 Engine Mount']);
        Listing::factory()->create(['title' => 'CRX Tail Light']);

        $response = $this->actingAs($user)
            ->get(route('admin.listings.index', ['q' => 'engine']), ['HX-Request' => 'true']);

        $response
            ->assertViewIs('admin.listings.partials.table')
            ->assertSee('B18 Engine Mount')
            ->assertDontSee('CRX Tail Light')
            ->assertDontSee('Store management');
    }

    public function test_pagination_links_preserve_the_search_query(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);

        Listing::factory()->create(['title' => 'B18 Engine Mount']);

        $response = $this->actingAs($user)->get(route('admin.listings.index', ['q' => 'engine']));

        $listings = $response->viewData('listings');

        $this->assertStringContainsString('q=engine', $listings->url(2));
    }

    public function test_search_query_is_escaped_in_the_input(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);

        $response = $this->actingAs($user)
            ->get(route('admin.listings.index', ['q' => '<script>alert(1)</script>']));

        $response
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}
