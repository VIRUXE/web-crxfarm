<?php

use App\Enums\ListingType;
use App\Enums\PartCategory;
use App\Support\ListingClassifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('listings', 'bolt_pattern')) {
            Schema::table('listings', function (Blueprint $table) {
                $table->string('bolt_pattern', 50)->nullable()->after('category')->index();
            });
        }

        // Migrate existing listings: split suspension_brakes_wheels into wheels_tires or suspension_brakes,
        // and assign bolt_pattern to all matching listings.
        $rows = DB::table('listings')->get();

        foreach ($rows as $row) {
            $isCar = $row->type === 'car' || $row->type === ListingType::Car->value;
            $text = trim($row->title.' '.($row->description ?? ''));

            $boltPattern = $isCar ? null : ListingClassifier::detectBoltPattern($text);

            $updates = [];

            if ($boltPattern !== null) {
                $updates['bolt_pattern'] = $boltPattern;
            }

            // Fix old category value if present or reclassify if needed
            if ($row->category === 'suspension_brakes_wheels') {
                $classified = ListingClassifier::classify($row->title, $row->description, $row->price);
                $updates['category'] = $classified['category']?->value ?? PartCategory::SuspensionBrakes->value;
            }

            if (! empty($updates)) {
                DB::table('listings')->where('id', $row->id)->update($updates);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('listings', 'bolt_pattern')) {
            Schema::table('listings', function (Blueprint $table) {
                $table->dropColumn('bolt_pattern');
            });
        }
    }
};
