<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            // Which photo represents the listing in the catalog grid and
            // social/OG previews. Null means "fall back to the first photo".
            $table->foreignId('thumbnail_image_id')->nullable()
                ->after('id')
                ->constrained('listing_images')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('thumbnail_image_id');
        });
    }
};
