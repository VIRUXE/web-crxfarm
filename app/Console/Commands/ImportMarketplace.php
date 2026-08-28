<?php

namespace App\Console\Commands;

use App\Models\Listing;
use App\Models\ListingImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use PDO;

class ImportMarketplace extends Command
{
    protected $signature = 'import:marketplace {--path= : Path to the scraped SQLite file}';

    protected $description = 'Import scraped Facebook Marketplace listings (SQLite staging file) into the live MySQL catalog';

    public function handle(): int
    {
        $path = $this->option('path')
            ?: '/tmp/claude-0/-root-crxfarm/a002b531-3e9a-4ed4-be25-1eaf6c4e73c2/scratchpad/crxfarm_listings.sqlite';

        if (! file_exists($path)) {
            $this->warn("Scrape file not found at {$path} — nothing to import yet.");

            return self::SUCCESS;
        }

        $pdo = new PDO('sqlite:'.$path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $listings = $pdo->query('SELECT * FROM listings')->fetchAll(PDO::FETCH_ASSOC);
        $this->info('Found '.count($listings).' scraped listings.');

        $imported = 0;
        foreach ($listings as $row) {
            $listing = Listing::updateOrCreate(
                ['source_marketplace_id' => $row['id']],
                [
                    'type' => 'part',
                    'title' => $row['title'] ?: 'Untitled listing',
                    'price' => $row['price'],
                    'description' => $row['description'],
                    'location' => $row['location'],
                    'status' => 'available',
                ]
            );

            $stmt = $pdo->prepare('SELECT * FROM images WHERE listing_id = :id ORDER BY seq');
            $stmt->execute(['id' => $row['id']]);
            $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($images && $listing->images()->count() === 0) {
                foreach ($images as $img) {
                    $ext = str_contains($img['mime_type'] ?? '', 'png') ? 'png' : 'jpg';
                    $filename = 'listings/'.uniqid('mkt_').'.'.$ext;
                    Storage::disk('public')->put($filename, $img['data']);

                    ListingImage::create([
                        'listing_id' => $listing->id,
                        'path' => $filename,
                        'seq' => (int) $img['seq'],
                    ]);
                }
            }

            $imported++;
        }

        $this->info("Imported/updated {$imported} listings.");

        return self::SUCCESS;
    }
}
