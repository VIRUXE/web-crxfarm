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
        Schema::table('users', function (Blueprint $table) {
            // invited -> pin_set -> active. Only 'active' users may log in
            // (passkey primary, PIN fallback). 'invited'/'pin_set' users are
            // mid-onboarding and blocked from every auth path until a passkey
            // is enrolled.
            $table->string('status')->default('invited')->after('is_admin');
            $table->string('pin_hash')->nullable()->after('password');
            $table->timestamp('pin_set_at')->nullable()->after('pin_hash');
            $table->timestamp('passkey_enrolled_at')->nullable()->after('pin_set_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['status', 'pin_hash', 'pin_set_at', 'passkey_enrolled_at']);
        });
    }
};
