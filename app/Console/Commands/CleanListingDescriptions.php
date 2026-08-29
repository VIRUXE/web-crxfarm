<?php

namespace App\Console\Commands;

use App\Models\Listing;
use App\Support\DescriptionCleaner;
use Illuminate\Console\Command;

class CleanListingDescriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'listings:clean-descriptions {--dry-run : Show description changes without saving to the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean all listing descriptions to strip Marketplace scrape artifacts and contact noise';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $listings = Listing::whereNotNull('description')->get();

        $updated = 0;
        $unchanged = 0;

        $this->info("Processing {$listings->count()} listings with descriptions...");

        $changes = [];

        foreach ($listings as $listing) {
            $original = $listing->description;
            $cleaned = DescriptionCleaner::clean($original);

            if ($cleaned !== $original) {
                $updated++;
                $changes[] = [
                    'id' => $listing->id,
                    'title' => $listing->title,
                    'before_len' => mb_strlen((string) $original),
                    'after_len' => mb_strlen((string) $cleaned),
                ];

                if (! $dryRun) {
                    $listing->description = $cleaned;
                    $listing->save();
                }
            } else {
                $unchanged++;
            }
        }

        if (! empty($changes)) {
            $this->table(
                ['ID', 'Listing Title', 'Original Len', 'Cleaned Len'],
                array_slice($changes, 0, 30)
            );

            if (count($changes) > 30) {
                $this->line('... and '.(count($changes) - 30).' more listings cleaned.');
            }
        }

        if ($dryRun) {
            $this->warn("[DRY RUN] Would clean {$updated} descriptions. {$unchanged} descriptions already clean.");
        } else {
            $this->info("Successfully cleaned {$updated} listing descriptions. {$unchanged} were already clean.");
        }

        return self::SUCCESS;
    }
}
