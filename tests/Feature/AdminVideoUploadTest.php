<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use App\Support\VideoConverter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class AdminVideoUploadTest extends TestCase
{
    private function makeMp4(): string
    {
        $path = sys_get_temp_dir().'/crxtest_'.Str::random(8).'.mp4';
        $p = new Process(['ffmpeg', '-hide_banner', '-loglevel', 'error', '-y',
            '-f', 'lavfi', '-i', 'testsrc=size=640x480:rate=10:duration=1',
            '-c:v', 'libx264', $path]);
        $p->run();

        return $path;
    }

    public function test_admin_upload_converts_video_to_webm_with_poster(): void
    {
        if (! VideoConverter::ffmpegAvailable()) {
            $this->markTestSkipped('ffmpeg not available');
        }

        Storage::fake('public');
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);

        $mp4 = $this->makeMp4();
        try {
            $upload = new UploadedFile($mp4, 'clip.mp4', 'video/mp4', null, true);

            $response = $this->actingAs($user)->post(route('admin.listings.store'), [
                'type' => 'part',
                'title' => 'CRX Video Part',
                'status' => 'available',
                'videos' => [$upload],
            ]);

            $listing = Listing::firstWhere('title', 'CRX Video Part');
            $this->assertNotNull($listing);
            $response->assertRedirect(route('admin.listings.edit', $listing));

            $video = $listing->videos()->first();
            $this->assertNotNull($video);
            $this->assertStringEndsWith('.webm', $video->path);
            $this->assertStringEndsWith('.webp', (string) $video->poster_path); // watermarked poster
            Storage::disk('public')->assertExists($video->path);
            Storage::disk('public')->assertExists($video->poster_path);
        } finally {
            @unlink($mp4);
        }
    }

    public function test_admin_can_delete_a_video(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('listings/videos/x.webm', 'data');
        Storage::disk('public')->put('listings/videos/x.webp', 'poster');

        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $listing = Listing::factory()->create();
        $video = $listing->videos()->create([
            'path' => 'listings/videos/x.webm',
            'poster_path' => 'listings/videos/x.webp',
            'seq' => 0,
        ]);

        $this->actingAs($user)->delete(route('admin.videos.destroy', $video))->assertOk();

        $this->assertModelMissing($video);
        Storage::disk('public')->assertMissing('listings/videos/x.webm');
        Storage::disk('public')->assertMissing('listings/videos/x.webp');
    }

    public function test_non_video_upload_is_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);

        $notVideo = UploadedFile::fake()->create('notes.txt', 4, 'text/plain');

        $this->actingAs($user)->post(route('admin.listings.store'), [
            'type' => 'part',
            'title' => 'No Video Part',
            'status' => 'available',
            'videos' => [$notVideo],
        ])->assertSessionHasErrors('videos.0');
    }
}
