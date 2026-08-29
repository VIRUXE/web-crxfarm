<?php

use App\Support\TitleNormalizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $listings = DB::table('listings')->select('id', 'title')->get();

        foreach ($listings as $listing) {
            if ($listing->title === null || $listing->title === '') {
                continue;
            }

            $normalized = TitleNormalizer::normalize($listing->title);

            if ($normalized !== $listing->title) {
                DB::table('listings')
                    ->where('id', $listing->id)
                    ->update(['title' => $normalized]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Title normalization is not reversible to noisy strings.
    }
};
