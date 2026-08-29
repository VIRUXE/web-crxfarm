<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use Tests\TestCase;

class AdminImageManagerTest extends TestCase
{
    public function test_guest_cannot_view_the_image_manager(): void
    {
        $this->get(route('admin.images.index'))->assertRedirect();
    }

    public function test_admin_sees_all_photos_grouped_by_listing(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);

        $withPhoto = Listing::factory()->create(['title' => 'CRX Hood']);
        $withPhoto->images()->create(['path' => 'listings/a-0.webp', 'seq' => 0]);
        Listing::factory()->create(['title' => 'No Photo Part']);

        $response = $this->actingAs($user)->get(route('admin.images.index'));

        $response->assertOk()
            ->assertSee('CRX Hood')
            ->assertSee('listings/a-0.webp', false) // image src rendered
            ->assertSee(route('admin.images.destroy', $withPhoto->images->first()), false);
    }

    public function test_missing_filter_shows_only_listings_without_photos(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);

        $withPhoto = Listing::factory()->create(['title' => 'Has Photo']);
        $withPhoto->images()->create(['path' => 'listings/b-0.webp', 'seq' => 0]);
        Listing::factory()->create(['title' => 'Needs A Photo']);

        $response = $this->actingAs($user)->get(route('admin.images.index', ['missing' => 1]));

        $response->assertOk()
            ->assertSee('Needs A Photo')
            ->assertDontSee('Has Photo');
    }

    public function test_admin_can_delete_a_photo_from_the_manager(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $listing = Listing::factory()->create();
        $image = $listing->images()->create(['path' => 'listings/c-0.webp', 'seq' => 0]);

        $this->actingAs($user)
            ->delete(route('admin.images.destroy', $image))
            ->assertOk();

        $this->assertModelMissing($image);
    }
}
