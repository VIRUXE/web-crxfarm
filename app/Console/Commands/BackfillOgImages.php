<?php

namespace App\Console\Commands;

use App\Models\ListingImage;
use App\Support\OgImageGenerator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:backfill-og-images')]
#[Description('Generate the 1200x630 og:image variant for listing photos uploaded before this existed')]
class BackfillOgImages extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $images = ListingImage::whereNull('og_path')->get();

        $this->info("Backfilling {$images->count()} photo(s)...");

        $failed = 0;
        $bar = $this->output->createProgressBar($images->count());
        $bar->start();

        foreach ($images as $image) {
            $ogPath = OgImageGenerator::generate($image->path, 'public');
            if ($ogPath === null) {
                $failed++;
            } else {
                $image->update(['og_path' => $ogPath]);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        if ($failed > 0) {
            $this->warn("Done, but {$failed} photo(s) failed to process (missing/corrupt source file).");
        } else {
            $this->info('Done.');
        }

        return self::SUCCESS;
    }
}
