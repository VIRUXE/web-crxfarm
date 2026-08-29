<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminListingImageUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_images_which_are_watermarked_and_converted_to_webp(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);

        $jpgFile = UploadedFile::fake()->image('part1.jpg', 600, 400);
        $pngFile = UploadedFile::fake()->image('part2.png', 500, 500);

        $response = $this->actingAs($user)->post(route('admin.listings.store'), [
            'type' => 'part',
            'title' => 'CRX Si Rear Bumper',
            'status' => 'available',
            'images' => [$jpgFile, $pngFile],
        ]);

        $listing = Listing::first();
        $this->assertNotNull($listing);
        $response->assertRedirect(route('admin.listings.edit', $listing));

        $images = $listing->images()->orderBy('seq')->get();
        $this->assertCount(2, $images);

        foreach ($images as $index => $image) {
            $this->assertSame($index, $image->seq);
            $this->assertTrue(Str::endsWith($image->path, '.webp'), "Expected path '{$image->path}' to end with .webp");
            Storage::disk('public')->assertExists($image->path);

            $contents = Storage::disk('public')->get($image->path);
            $imageInfo = getimagesizefromstring($contents);
            $this->assertIsArray($imageInfo);
            $this->assertSame('image/webp', $imageInfo['mime']);

            $gd = imagecreatefromstring($contents);
            $this->assertNotFalse($gd);

            // The uploaded fake image is a single solid colour; the tiled
            // watermark stamps translucent white text across it, so the
            // processed image should span a wide brightness range (a plain
            // fill would be near-uniform). This is agnostic to the base colour.
            $w = imagesx($gd);
            $h = imagesy($gd);
            $min = 765;
            $max = 0;
            for ($x = 0; $x < $w; $x += 3) {
                for ($y = 0; $y < $h; $y += 3) {
                    $rgb = imagecolorat($gd, $x, $y);
                    $brightness = (($rgb >> 16) & 0xFF) + (($rgb >> 8) & 0xFF) + ($rgb & 0xFF);
                    $min = min($min, $brightness);
                    $max = max($max, $brightness);
                }
            }
            $this->assertGreaterThan(80, $max - $min, 'Expected the tiled watermark to vary brightness across the uploaded image.');
        }
    }

    public function test_admin_can_upload_additional_images_when_updating_listing(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $listing = Listing::factory()->create(['status' => 'available']);
        $listing->images()->create([
            'path' => 'listings/existing.webp',
            'seq' => 0,
        ]);
        Storage::disk('public')->put('listings/existing.webp', 'fake');

        $newImage = UploadedFile::fake()->image('extra.jpg', 640, 480);

        $response = $this->actingAs($user)->put(route('admin.listings.update', $listing), [
            'type' => 'part',
            'title' => $listing->title,
            'status' => 'available',
            'images' => [$newImage],
        ]);

        $response->assertRedirect(route('admin.listings.edit', $listing));

        $images = $listing->images()->orderBy('seq')->get();
        $this->assertCount(2, $images);

        $this->assertSame(0, $images[0]->seq);
        $this->assertSame('listings/existing.webp', $images[0]->path);

        $this->assertSame(1, $images[1]->seq);
        $this->assertTrue(Str::endsWith($images[1]->path, '.webp'));
        Storage::disk('public')->assertExists($images[1]->path);

        $contents = Storage::disk('public')->get($images[1]->path);
        $imageInfo = getimagesizefromstring($contents);
        $this->assertSame('image/webp', $imageInfo['mime']);
    }

    public function test_admin_can_upload_already_webp_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);

        $im = imagecreatetruecolor(300, 300);
        $color = imagecolorallocate($im, 100, 150, 200);
        imagefilledrectangle($im, 0, 0, 299, 299, $color);
        ob_start();
        imagewebp($im, null, 90);
        $webpContent = ob_get_clean();

        $webpFile = UploadedFile::fake()->createWithContent('photo.webp', $webpContent);

        $response = $this->actingAs($user)->post(route('admin.listings.store'), [
            'type' => 'part',
            'title' => 'CRX Si Steering Wheel',
            'status' => 'available',
            'images' => [$webpFile],
        ]);

        $listing = Listing::first();
        $this->assertNotNull($listing);
        $response->assertRedirect(route('admin.listings.edit', $listing));

        $image = $listing->images()->first();
        $this->assertNotNull($image);
        $this->assertTrue(Str::endsWith($image->path, '.webp'));
        Storage::disk('public')->assertExists($image->path);

        $contents = Storage::disk('public')->get($image->path);
        $imageInfo = getimagesizefromstring($contents);
        $this->assertSame('image/webp', $imageInfo['mime']);
    }
}
