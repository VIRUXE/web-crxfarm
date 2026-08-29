<?php

namespace App\Console\Commands;

use App\Enums\ListingType;
use App\Models\Chassis;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Support\ImageTrimmer;
use App\Support\ListingClassifier;
use App\Support\TitleNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ImportMarketplace extends Command
{
    private const IMAGE_USER_AGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';

    protected $signature = 'import:marketplace
        {--path= : Path to listings.jsonl or a scrape SQLite file}
        {--images= : Directory of image files named {marketplace-id}.jpg}
        {--honda-only : Skip listings the classifier does not consider Honda-related}
        {--force : Re-extract photos even if the listing already has images}';

    protected $description = 'Import scraped Marketplace listings into MariaDB, classifying type/category/chassis and converting image blobs to public files';

    public function handle(): int
    {
        $path = $this->option('path');

        if (! is_string($path) || $path === '') {
            $this->error('Pass --path to a listings.jsonl file or a scrape SQLite database.');

            return self::FAILURE;
        }

        if (! is_file($path)) {
            $this->error("Scrape file not found at {$path}.");

            return self::FAILURE;
        }

        $imagesDir = $this->option('images');
        $imagesDir = is_string($imagesDir) && $imagesDir !== ''
            ? $imagesDir
            : dirname($path).DIRECTORY_SEPARATOR.'crxfarm_images';

        try {
            $rows = $this->isSqlite($path)
                ? $this->listingsFromSqlite($path)
                : $this->listingsFromJsonl($path);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Found '.count($rows).' scraped listings.');

        $hondaOnly = (bool) $this->option('honda-only');
        $imported = 0;
        $skipped = 0;
        foreach ($rows as $row) {
            $sourceId = (string) $row['id'];
            $rawTitle = ($row['title'] ?? '') !== '' ? (string) $row['title'] : 'Untitled listing';
            $title = TitleNormalizer::normalize($rawTitle);

            $classified = ListingClassifier::classify($title, $row['description'] ?? null, $row['price'] ?? null);

            if ($hondaOnly && ! $classified['honda']) {
                $skipped++;

                continue;
            }

            // Cars keep their single chassis on the column; parts use the pivot.
            $isCar = $classified['type'] === ListingType::Car;

            $listing = $this->adoptOrCreate($sourceId, $title, [
                'type' => $classified['type'],
                'title' => $title,
                'chassis' => $isCar ? ($classified['chassis'][0] ?? null) : null,
                'category' => $isCar ? null : $classified['category'],
                'bolt_pattern' => $isCar ? null : ($classified['bolt_pattern'] ?? null),
                'price' => $classified['clean_price'],
                'description' => $row['description'] ?? null,
                // Location is intentionally dropped: every listing is the
                // seller's home area, so it carries no signal for the catalog.
                'location' => null,
                'status' => 'available',
            ]);

            if (! $isCar) {
                $ids = array_map(
                    fn (string $name) => Chassis::firstOrCreate(['name' => $name])->id,
                    $classified['chassis'],
                );
                $listing->compatibleChassis()->sync($ids);
            }

            if ($this->option('force')) {
                foreach ($listing->images as $existing) {
                    Storage::disk('public')->delete($existing->path);
                    $existing->delete();
                }
                $listing->unsetRelation('images');
            }

            $existingBySeq = $listing->images()->get()->keyBy('seq');

            foreach ($row['images'] as $image) {
                $seq = (int) $image['seq'];

                // Remote photos are the slow part; once downloaded, leave them
                // be unless --force, so re-imports (as new scrapes land) skip
                // what is already stored.
                if (($image['url'] ?? null) !== null && ! $this->option('force') && $existingBySeq->has($seq)) {
                    continue;
                }

                $pathOnDisk = $this->storeImage($sourceId, $image, is_dir($imagesDir) ? $imagesDir : null);

                if ($pathOnDisk === null) {
                    continue;
                }

                $existing = $existingBySeq->get($seq);

                if ($existing !== null) {
                    if ($existing->path !== $pathOnDisk) {
                        Storage::disk('public')->delete($existing->path);
                        $existing->update(['path' => $pathOnDisk]);
                    }

                    continue;
                }

                ListingImage::create([
                    'listing_id' => $listing->id,
                    'path' => $pathOnDisk,
                    'seq' => $seq,
                ]);
            }

            $imported++;
        }

        $this->info("Imported/updated {$imported} listings.");

        if ($hondaOnly) {
            $this->info("Skipped {$skipped} non-Honda listings.");
        }

        return self::SUCCESS;
    }

    /**
     * Upsert by marketplace id, but first adopt a listing that was entered by
     * hand with the same title and no source id, so re-importing the seller's
     * catalog does not duplicate the ones already added manually.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function adoptOrCreate(string $sourceId, string $title, array $attributes): Listing
    {
        $listing = Listing::query()->where('source_marketplace_id', $sourceId)->first()
            ?? Listing::query()
                ->whereNull('source_marketplace_id')
                ->whereRaw('LOWER(title) = ?', [mb_strtolower($title)])
                ->first();

        if ($listing !== null) {
            $listing->fill([...$attributes, 'source_marketplace_id' => $sourceId])->save();

            return $listing;
        }

        return Listing::create([...$attributes, 'source_marketplace_id' => $sourceId]);
    }

    /**
     * @return list<array{id: string, title: ?string, price: ?string, description: ?string, location: ?string, images: list<array{seq: int, mime_type: ?string, data: ?string, url?: ?string}>}>
     */
    private function listingsFromJsonl(string $path): array
    {
        $rows = [];

        foreach (File::lines($path) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
            $sourceId = (string) ($decoded['id'] ?? '');

            // The batch scraper writes {id, error:{...}} lines for listings it
            // could not fetch; there is nothing to import from those.
            if ($sourceId === '' || isset($decoded['error'])) {
                continue;
            }

            $title = isset($decoded['title']) ? (string) $decoded['title'] : null;
            $location = isset($decoded['location']) ? (string) $decoded['location'] : null;

            // Facebook renders a struck-through original price on discounted
            // listings, which shuffles the scraped title/location: the title
            // comes through as a bare "$1,800" and the real title lands in
            // location. Swap them back.
            if ($title !== null && preg_match('/^\$[\d,]+$/', $title) && $location !== null) {
                [$title, $location] = [$location, null];
            }

            // Prefer the human price string ("$100") over the numeric field.
            $price = $decoded['priceText'] ?? $decoded['price'] ?? null;

            $rows[] = [
                'id' => $sourceId,
                'title' => $title,
                'price' => $price !== null ? (string) $price : null,
                'description' => isset($decoded['description']) ? (string) $decoded['description'] : null,
                'location' => $location,
                // Fall back to a single placeholder so the --images directory
                // lookup still works for scrapes that carry no photo URLs.
                'images' => $this->imageUrlsFrom($decoded['images'] ?? null)
                    ?: [['seq' => 0, 'mime_type' => null, 'data' => null, 'url' => null]],
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{id: string, title: ?string, price: ?string, description: ?string, location: ?string, images: list<array{seq: int, mime_type: ?string, data: ?string, url?: ?string}>}>
     */
    private function listingsFromSqlite(string $path): array
    {
        config([
            'database.connections.marketplace_scrape' => [
                'driver' => 'sqlite',
                'database' => $path,
                'prefix' => '',
                'foreign_key_constraints' => false,
            ],
        ]);

        try {
            $listings = DB::connection('marketplace_scrape')->table('listings')->get();
            $rows = [];

            foreach ($listings as $listing) {
                $sourceId = (string) $listing->id;
                $images = DB::connection('marketplace_scrape')
                    ->table('images')
                    ->where('listing_id', $sourceId)
                    ->orderBy('seq')
                    ->get();

                $imageRows = [];
                foreach ($images as $image) {
                    $data = $image->data;
                    if (is_resource($data)) {
                        $data = stream_get_contents($data);
                    }

                    $imageRows[] = [
                        'seq' => (int) $image->seq,
                        'mime_type' => isset($image->mime_type) ? (string) $image->mime_type : null,
                        'data' => is_string($data) && $data !== '' ? $data : null,
                        'url' => null,
                    ];
                }

                $rows[] = [
                    'id' => $sourceId,
                    'title' => isset($listing->title) ? (string) $listing->title : null,
                    'price' => isset($listing->price) ? (string) $listing->price : null,
                    'description' => isset($listing->description) ? (string) $listing->description : null,
                    'location' => isset($listing->location) ? (string) $listing->location : null,
                    'images' => $imageRows,
                ];
            }

            return $rows;
        } finally {
            DB::purge('marketplace_scrape');
        }
    }

    /**
     * @param  array{seq: int, mime_type: ?string, data: ?string, url?: ?string}  $image
     */
    private function storeImage(string $sourceId, array $image, ?string $imagesDir): ?string
    {
        if ($image['data'] !== null) {
            $path = $this->storagePath($sourceId, $image['seq'], $this->extensionFromMime($image['mime_type']));
            Storage::disk('public')->put($path, ImageTrimmer::trim($image['data']));

            return $path;
        }

        if (($image['url'] ?? null) !== null) {
            return $this->storeRemoteImage($sourceId, $image);
        }

        $fromFile = $imagesDir !== null
            ? $this->imageFileForListing($imagesDir, $sourceId, $image['seq'])
            : null;

        if ($fromFile === null) {
            return null;
        }

        $extension = pathinfo($fromFile, PATHINFO_EXTENSION) ?: $this->extensionFromMime($image['mime_type']);
        $path = $this->storagePath($sourceId, $image['seq'], $extension);
        Storage::disk('public')->put($path, ImageTrimmer::trim(File::get($fromFile)));

        return $path;
    }

    /**
     * @param  array{seq: int, mime_type: ?string, data: ?string, url?: ?string}  $image
     */
    private function storeRemoteImage(string $sourceId, array $image): ?string
    {
        $url = (string) $image['url'];

        try {
            $response = Http::withHeaders(['User-Agent' => self::IMAGE_USER_AGENT])
                ->timeout(25)
                ->retry(2, 500)
                ->get($url);
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $bytes = $response->body();

        if ($bytes === '') {
            return null;
        }

        // Facebook listing galleries can include videos, and their URLs return
        // MP4 bytes. Only store real raster images; otherwise process() would
        // fall back to saving the raw (non-image) bytes as a broken .webp.
        $info = @getimagesizefromstring($bytes);
        if ($info === false || ! str_starts_with((string) ($info['mime'] ?? ''), 'image/')) {
            return null;
        }

        // process() trims borders, downscales, stamps the CRXFARM watermark,
        // and converts to WebP, so every stored photo is web-sized and marked.
        $path = $this->storagePath($sourceId, $image['seq'], 'webp');
        Storage::disk('public')->put($path, ImageTrimmer::process($bytes));

        return $path;
    }

    /**
     * Keep only real listing photos (Facebook CDN images), dropping UI icons
     * and avatars, and number them in order.
     *
     * @param  mixed  $images
     * @return list<array{seq: int, mime_type: ?string, data: ?string, url: string}>
     */
    private function imageUrlsFrom($images): array
    {
        if (! is_array($images)) {
            return [];
        }

        $rows = [];
        $seq = 0;
        foreach ($images as $url) {
            if (! is_string($url) || $url === '') {
                continue;
            }

            $host = (string) parse_url($url, PHP_URL_HOST);
            if (! str_contains($host, 'fbcdn.net') || str_starts_with($host, 'static.')) {
                continue;
            }

            $rows[] = ['seq' => $seq++, 'mime_type' => null, 'data' => null, 'url' => $url];
        }

        return $rows;
    }

    private function imageFileForListing(string $imagesDir, string $sourceId, int $seq): ?string
    {
        $candidates = [
            $imagesDir.DIRECTORY_SEPARATOR.$sourceId.'.jpg',
            $imagesDir.DIRECTORY_SEPARATOR.$sourceId.'.jpeg',
            $imagesDir.DIRECTORY_SEPARATOR.$sourceId.'.png',
            $imagesDir.DIRECTORY_SEPARATOR.$sourceId.'_'.$seq.'.jpg',
            $imagesDir.DIRECTORY_SEPARATOR.$sourceId.'_'.$seq.'.jpeg',
            $imagesDir.DIRECTORY_SEPARATOR.$sourceId.'_'.$seq.'.png',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function storagePath(string $sourceId, int $seq, string $extension): string
    {
        return 'listings/'.$sourceId.'-'.$seq.'.'.strtolower($extension);
    }

    private function extensionFromMime(?string $mime): string
    {
        return str_contains((string) $mime, 'png') ? 'png' : 'jpg';
    }

    private function isSqlite(string $path): bool
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        $header = fread($handle, 16);
        fclose($handle);

        return is_string($header) && str_starts_with($header, 'SQLite format 3');
    }
}
