<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $names = DB::table('listings')
            ->whereNotNull('chassis')
            ->where('chassis', '!=', '')
            ->distinct()
            ->pluck('chassis');

        $chassisIds = [];
        foreach ($names as $name) {
            $chassisIds[$name] = DB::table('chassis')->insertGetId([
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $parts = DB::table('listings')
            ->where('type', 'part')
            ->whereNotNull('chassis')
            ->where('chassis', '!=', '')
            ->get(['id', 'chassis']);

        foreach ($parts as $part) {
            DB::table('listing_chassis')->insert([
                'listing_id' => $part->id,
                'chassis_id' => $chassisIds[$part->chassis],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('listing_chassis')->truncate();
        DB::table('chassis')->truncate();
    }
};
