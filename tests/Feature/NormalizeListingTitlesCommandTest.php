<?php

namespace Tests\Feature;

use App\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NormalizeListingTitlesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_normalizes_listing_titles_in_database(): void
    {
        $id1 = DB::table('listings')->insertGetId([
            'title' => '1992 honda civic vx hatchback 2d',
            'type' => 'car',
            'status' => 'available',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id2 = DB::table('listings')->insertGetId([
            'title' => 'b18c1 bare block gsr',
            'type' => 'part',
            'status' => 'available',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id3 = DB::table('listings')->insertGetId([
            'title' => 'CRX Sunroof Panels',
            'type' => 'part',
            'status' => 'available',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('listings:normalize-titles')
            ->expectsOutputToContain('Successfully normalized 2 listing titles')
            ->assertSuccessful();

        $this->assertSame('1992 Honda Civic VX Hatchback 2D', DB::table('listings')->where('id', $id1)->value('title'));
        $this->assertSame('B18C1 Bare Block GSR', DB::table('listings')->where('id', $id2)->value('title'));
        $this->assertSame('CRX Sunroof Panels', DB::table('listings')->where('id', $id3)->value('title'));
    }

    public function test_command_dry_run_does_not_mutate_database(): void
    {
        $id = DB::table('listings')->insertGetId([
            'title' => '1992 honda civic vx hatchback 2d',
            'type' => 'car',
            'status' => 'available',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('listings:normalize-titles --dry-run')
            ->expectsOutputToContain('[DRY RUN] Would update 1 titles')
            ->assertSuccessful();

        $this->assertSame('1992 honda civic vx hatchback 2d', DB::table('listings')->where('id', $id)->value('title'));
    }

    public function test_listing_model_normalizes_title_on_set(): void
    {
        $listing = Listing::factory()->create([
            'title' => '1996 honda del sol si coupe 2d',
        ]);

        $this->assertSame('1996 Honda Del Sol Si Coupe 2D', $listing->title);
    }
}
