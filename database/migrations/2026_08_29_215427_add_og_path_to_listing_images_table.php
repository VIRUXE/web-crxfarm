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
        Schema::table('listing_images', function (Blueprint $table) {
            // Pre-rendered 1200x630 cover-crop of `path`, used for og:image /
            // twitter:image so social cards never show a cropped or
            // letterboxed portrait photo. Null until backfilled.
            $table->string('og_path')->nullable()->after('path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listing_images', function (Blueprint $table) {
            $table->dropColumn('og_path');
        });
    }
};
