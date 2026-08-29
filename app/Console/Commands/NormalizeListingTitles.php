<?php

namespace App\Console\Commands;

use App\Models\Listing;
use App\Support\TitleNormalizer;
use Illuminate\Console\Command;

class NormalizeListingTitles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'listings:normalize-titles {--dry-run : Show title changes without saving to the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Normalize all listing titles to consistent Title Case, correcting domain acronyms, engine codes, and chassis names';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $listings = Listing::all();

        $updated = 0;
        $unchanged = 0;

        $this->info("Processing {$listings->count()} listings...");

        $changes = [];

        foreach ($listings as $listing) {
            $original = $listing->getRawOriginal('title') ?? $listing->title;
            $normalized = TitleNormalizer::normalize((string) $original);

            if ($normalized !== $original) {
                $updated++;
                $changes[] = [
                    'id' => $listing->id,
                    'before' => $original,
                    'after' => $normalized,
                ];

                if (! $dryRun) {
                    $listing->title = $normalized;
                    $listing->save();
                }
            } else {
                $unchanged++;
            }
        }

        if (! empty($changes)) {
            $this->table(
                ['ID', 'Original Title', 'Normalized Title'],
                array_slice($changes, 0, 50)
            );

            if (count($changes) > 50) {
                $this->line('... and '.(count($changes) - 50).' more changes.');
            }
        }

        if ($dryRun) {
            $this->warn("[DRY RUN] Would update {$updated} titles. {$unchanged} titles already normalized.");
        } else {
            $this->info("Successfully normalized {$updated} listing titles. {$unchanged} titles were already normalized.");
        }

        return self::SUCCESS;
    }
}
