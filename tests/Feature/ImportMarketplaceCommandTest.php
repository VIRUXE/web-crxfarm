<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\ListingImage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PDO;
use Tests\TestCase;

class ImportMarketplaceCommandTest extends TestCase
{
    public function test_import_writes_sqlite_blobs_to_public_files(): void
    {
        Storage::fake('public');

        $dir = sys_get_temp_dir().'/crxfarm-import-'.uniqid();
        File::ensureDirectoryExists($dir);
        $sqlite = $dir.'/listings.sqlite';
        $jpeg = "\xFF\xD8\xFF\xE0".str_repeat('A', 64);

        try {
            $pdo = new PDO('sqlite:'.$sqlite);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec('CREATE TABLE listings (id TEXT, url TEXT, title TEXT, price TEXT, description TEXT, location TEXT, scraped_at TEXT)');
            $pdo->exec('CREATE TABLE images (id INTEGER PRIMARY KEY, listing_id TEXT, seq INTEGER, mime_type TEXT, data BLOB)');
            $pdo->prepare('INSERT INTO listings (id, title, price, description, location) VALUES (?, ?, ?, ?, ?)')
                ->execute(['mkt-100', 'CRX Hood', '$200', 'Bare hood', 'Topeka, KS']);
            $stmt = $pdo->prepare('INSERT INTO images (listing_id, seq, mime_type, data) VALUES (?, ?, ?, ?)');
            $stmt->bindValue(1, 'mkt-100');
            $stmt->bindValue(2, 0, PDO::PARAM_INT);
            $stmt->bindValue(3, 'image/jpeg');
            $stmt->bindValue(4, $jpeg, PDO::PARAM_LOB);
            $stmt->execute();
            $pdo = null;

            $this->artisan('import:marketplace', ['--path' => $sqlite])
                ->expectsOutputToContain('Found 1 scraped listings.')
                ->assertSuccessful();
        } finally {
            File::deleteDirectory($dir);
        }

        $this->assertDatabaseHas('listings', [
            'source_marketplace_id' => 'mkt-100',
            'title' => 'CRX Hood',
            'price' => '$200',
            'location' => null,
        ]);

        $image = ListingImage::query()->first();
        $this->assertNotNull($image);
        $this->assertSame('listings/mkt-100-0.jpg', $image->path);
        Storage::disk('public')->assertExists('listings/mkt-100-0.jpg');
        $this->assertSame($jpeg, Storage::disk('public')->get('listings/mkt-100-0.jpg'));
    }

    public function test_import_copies_image_files_from_disk_instead_of_keeping_blobs(): void
    {
        Storage::fake('public');

        $dir = sys_get_temp_dir().'/crxfarm-import-'.uniqid();
        $imagesDir = $dir.'/crxfarm_images';
        File::ensureDirectoryExists($imagesDir);
        $jsonl = $dir.'/listings.jsonl';
        $bytes = 'file-on-disk-not-a-sqlite-blob';

        try {
            File::put($jsonl, json_encode([
                'id' => '883122864666344',
                'title' => 'Whelen Light bar led',
                'price' => '$100',
                'description' => 'Has a crack',
                'location' => 'Topeka, KS',
            ])."\n");
            File::put($imagesDir.'/883122864666344.jpg', $bytes);

            $this->artisan('import:marketplace', [
                '--path' => $jsonl,
                '--images' => $imagesDir,
            ])->assertSuccessful();
        } finally {
            File::deleteDirectory($dir);
        }

        $listing = Listing::query()->where('source_marketplace_id', '883122864666344')->first();
        $this->assertNotNull($listing);
        $this->assertSame('Whelen Light Bar LED', $listing->title);

        $image = $listing->images()->first();
        $this->assertNotNull($image);
        $this->assertSame('listings/883122864666344-0.jpg', $image->path);
        Storage::disk('public')->assertExists($image->path);
        $this->assertSame($bytes, Storage::disk('public')->get($image->path));
    }

    public function test_import_converts_every_sqlite_blob_even_when_listing_already_has_a_photo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('listings/existing.jpg', 'kept');

        $listing = Listing::factory()->create([
            'source_marketplace_id' => 'mkt-200',
            'title' => 'Old title',
        ]);
        $listing->images()->create([
            'path' => 'listings/existing.jpg',
            'seq' => 0,
        ]);

        $dir = sys_get_temp_dir().'/crxfarm-import-'.uniqid();
        File::ensureDirectoryExists($dir);
        $sqlite = $dir.'/listings.sqlite';
        $first = "\xFF\xD8\xFF\xE0".str_repeat('B', 64);
        $second = "\xFF\xD8\xFF\xE0".str_repeat('C', 64);

        try {
            $pdo = new PDO('sqlite:'.$sqlite);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec('CREATE TABLE listings (id TEXT, url TEXT, title TEXT, price TEXT, description TEXT, location TEXT, scraped_at TEXT)');
            $pdo->exec('CREATE TABLE images (id INTEGER PRIMARY KEY, listing_id TEXT, seq INTEGER, mime_type TEXT, data BLOB)');
            $pdo->prepare('INSERT INTO listings (id, title, price, description, location) VALUES (?, ?, ?, ?, ?)')
                ->execute(['mkt-200', 'New title', '$50', 'Updated', 'Rossville, KS']);
            foreach ([0 => $first, 1 => $second] as $seq => $bytes) {
                $stmt = $pdo->prepare('INSERT INTO images (listing_id, seq, mime_type, data) VALUES (?, ?, ?, ?)');
                $stmt->bindValue(1, 'mkt-200');
                $stmt->bindValue(2, $seq, PDO::PARAM_INT);
                $stmt->bindValue(3, 'image/jpeg');
                $stmt->bindValue(4, $bytes, PDO::PARAM_LOB);
                $stmt->execute();
            }
            $pdo = null;

            $this->artisan('import:marketplace', ['--path' => $sqlite])
                ->assertSuccessful();
        } finally {
            File::deleteDirectory($dir);
        }

        $listing->refresh();
        $this->assertSame('New Title', $listing->title);
        $this->assertSame(2, $listing->images()->count());
        $this->assertSame('listings/mkt-200-0.jpg', $listing->images()->where('seq', 0)->value('path'));
        $this->assertSame('listings/mkt-200-1.jpg', $listing->images()->where('seq', 1)->value('path'));
        Storage::disk('public')->assertExists('listings/mkt-200-0.jpg');
        Storage::disk('public')->assertExists('listings/mkt-200-1.jpg');
        $this->assertSame($first, Storage::disk('public')->get('listings/mkt-200-0.jpg'));
        $this->assertSame($second, Storage::disk('public')->get('listings/mkt-200-1.jpg'));
        Storage::disk('public')->assertMissing('listings/existing.jpg');
    }

    public function test_force_reextracts_sqlite_blobs_over_existing_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('listings/mkt-300-0.jpg', 'stale');

        $listing = Listing::factory()->create(['source_marketplace_id' => 'mkt-300']);
        $listing->images()->create([
            'path' => 'listings/mkt-300-0.jpg',
            'seq' => 0,
        ]);

        $dir = sys_get_temp_dir().'/crxfarm-import-'.uniqid();
        File::ensureDirectoryExists($dir);
        $sqlite = $dir.'/listings.sqlite';
        $jpeg = "\xFF\xD8\xFF\xE0".str_repeat('D', 64);

        try {
            $pdo = new PDO('sqlite:'.$sqlite);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec('CREATE TABLE listings (id TEXT, url TEXT, title TEXT, price TEXT, description TEXT, location TEXT, scraped_at TEXT)');
            $pdo->exec('CREATE TABLE images (id INTEGER PRIMARY KEY, listing_id TEXT, seq INTEGER, mime_type TEXT, data BLOB)');
            $pdo->prepare('INSERT INTO listings (id, title) VALUES (?, ?)')->execute(['mkt-300', 'Forced']);
            $stmt = $pdo->prepare('INSERT INTO images (listing_id, seq, mime_type, data) VALUES (?, ?, ?, ?)');
            $stmt->bindValue(1, 'mkt-300');
            $stmt->bindValue(2, 0, PDO::PARAM_INT);
            $stmt->bindValue(3, 'image/jpeg');
            $stmt->bindValue(4, $jpeg, PDO::PARAM_LOB);
            $stmt->execute();
            $pdo = null;

            $this->artisan('import:marketplace', [
                '--path' => $sqlite,
                '--force' => true,
            ])->assertSuccessful();
        } finally {
            File::deleteDirectory($dir);
        }

        $this->assertSame($jpeg, Storage::disk('public')->get('listings/mkt-300-0.jpg'));
        $this->assertSame(1, $listing->images()->count());
    }

    public function test_import_fails_when_scrape_file_is_missing(): void
    {
        $this->artisan('import:marketplace', ['--path' => '/tmp/does-not-exist.jsonl'])
            ->assertFailed();
    }

    public function test_honda_only_skips_non_honda_and_classifies_the_rest(): void
    {
        $dir = sys_get_temp_dir().'/crxfarm-import-'.uniqid();
        File::ensureDirectoryExists($dir);
        $jsonl = $dir.'/listings.jsonl';

        try {
            $lines = [
                ['id' => 'h-1', 'title' => 'CRX Hatch Hood ef 88 to 91', 'priceText' => '$450'],
                ['id' => 'h-2', 'title' => '1992 Honda civic VX Hatchback 2D', 'priceText' => '$1,234'],
                ['id' => 'j-1', 'title' => '2002 Yamaha fx140', 'priceText' => '$3,900'],
            ];
            File::put($jsonl, implode("\n", array_map(fn ($l) => json_encode($l), $lines))."\n");

            $this->artisan('import:marketplace', ['--path' => $jsonl, '--honda-only' => true])
                ->expectsOutputToContain('Skipped 1 non-Honda listings.')
                ->assertSuccessful();
        } finally {
            File::deleteDirectory($dir);
        }

        $this->assertDatabaseMissing('listings', ['source_marketplace_id' => 'j-1']);

        $hood = Listing::query()->where('source_marketplace_id', 'h-1')->first();
        $this->assertNotNull($hood);
        $this->assertSame('part', $hood->type->value);
        $this->assertSame('exterior_body', $hood->category->value);
        // A part can fit several chassis; this hood title names both CRX and EF.
        $this->assertEqualsCanonicalizing(['CRX', 'EF'], $hood->compatibleChassis->pluck('name')->all());

        $car = Listing::query()->where('source_marketplace_id', 'h-2')->first();
        $this->assertNotNull($car);
        $this->assertSame('car', $car->type->value);
        $this->assertNull($car->category);
        $this->assertNull($car->price);
    }

    public function test_import_downloads_remote_photos_and_stores_them_watermarked(): void
    {
        Storage::fake('public');

        // A real 200x150 JPEG so GD can decode, trim, watermark, and re-encode.
        $im = imagecreatetruecolor(200, 150);
        imagefill($im, 0, 0, imagecolorallocate($im, 90, 90, 90));
        ob_start();
        imagejpeg($im);
        $jpeg = (string) ob_get_clean();

        Http::fake([
            'scontent-fra5-2.xx.fbcdn.net/*' => Http::response($jpeg, 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $dir = sys_get_temp_dir().'/crxfarm-import-'.uniqid();
        File::ensureDirectoryExists($dir);
        $jsonl = $dir.'/listings.jsonl';

        try {
            File::put($jsonl, json_encode([
                'id' => 'img-1',
                'title' => 'CRX Sunroof Panels',
                'priceText' => '$450',
                'images' => [
                    'https://scontent-fra5-2.xx.fbcdn.net/v/t39.84726-6/a.jpg?oh=x',
                    'https://scontent-fra5-2.xx.fbcdn.net/v/t45.5328-4/b.jpg?oh=y',
                    'https://static.xx.fbcdn.net/rsrc.php/icon.webp', // UI icon, must be skipped
                ],
            ])."\n");

            $this->artisan('import:marketplace', ['--path' => $jsonl])->assertSuccessful();
        } finally {
            File::deleteDirectory($dir);
        }

        $listing = Listing::query()->where('source_marketplace_id', 'img-1')->first();
        $this->assertNotNull($listing);

        // Two real photos stored (the static icon was dropped), as WebP.
        $this->assertSame(2, $listing->images()->count());
        $first = $listing->images()->where('seq', 0)->value('path');
        $this->assertSame('listings/img-1-0.webp', $first);
        Storage::disk('public')->assertExists('listings/img-1-0.webp');
        $this->assertStringStartsWith('RIFF', Storage::disk('public')->get($first)); // WebP container
    }

    public function test_import_adopts_hand_entered_listing_with_matching_title(): void
    {
        $manual = Listing::factory()->create([
            'source_marketplace_id' => null,
            'title' => 'CRX Sunroof Panels',
        ]);

        $dir = sys_get_temp_dir().'/crxfarm-import-'.uniqid();
        File::ensureDirectoryExists($dir);
        $jsonl = $dir.'/listings.jsonl';

        try {
            File::put($jsonl, json_encode([
                'id' => 'mkt-777',
                'title' => 'CRX Sunroof Panels',
                'priceText' => '$450',
            ])."\n");

            $this->artisan('import:marketplace', ['--path' => $jsonl])->assertSuccessful();
        } finally {
            File::deleteDirectory($dir);
        }

        // Adopted, not duplicated.
        $this->assertSame(1, Listing::query()->where('title', 'CRX Sunroof Panels')->count());
        $manual->refresh();
        $this->assertSame('mkt-777', $manual->source_marketplace_id);
    }

    public function test_import_skips_non_image_downloads_like_videos(): void
    {
        Storage::fake('public');

        // Facebook galleries include videos; their URLs return MP4 bytes.
        $mp4 = "\x00\x00\x00\x1cftypisom\x00\x00\x02\x00isomiso2mp41".str_repeat('x', 64);
        Http::fake([
            'scontent-fra5-2.xx.fbcdn.net/*' => Http::response($mp4, 200, ['Content-Type' => 'video/mp4']),
        ]);

        $dir = sys_get_temp_dir().'/crxfarm-import-'.uniqid();
        File::ensureDirectoryExists($dir);
        $jsonl = $dir.'/listings.jsonl';

        try {
            File::put($jsonl, json_encode([
                'id' => 'vid-1',
                'title' => 'CRX Sunroof Panels',
                'priceText' => '$450',
                'images' => ['https://scontent-fra5-2.xx.fbcdn.net/v/t39.30808-6/clip.mp4?oh=x'],
            ])."\n");

            $this->artisan('import:marketplace', ['--path' => $jsonl])->assertSuccessful();
        } finally {
            File::deleteDirectory($dir);
        }

        $listing = Listing::query()->where('source_marketplace_id', 'vid-1')->first();
        $this->assertNotNull($listing);
        // The video was not stored as a broken image.
        $this->assertSame(0, $listing->images()->count());
        $this->assertSame(0, Storage::disk('public')->allFiles('listings') === [] ? 0 : count(Storage::disk('public')->allFiles('listings')));
    }
}
