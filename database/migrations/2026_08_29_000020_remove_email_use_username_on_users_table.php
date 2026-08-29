<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drops email entirely — auth is PIN + passkey only now, no email
     * delivery anywhere in the app. `username` becomes the plain
     * human-readable identifier used for PIN-fallback lookup and admin
     * bookkeeping; it carries no delivery semantics.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->nullable()->after('name');
        });

        DB::table('users')->orderBy('id')->get()->each(function ($user) {
            $base = strtolower(preg_replace('/[^a-z0-9]+/i', '', explode('@', $user->email)[0]) ?: 'user');
            $username = $base;
            $i = 1;
            while (DB::table('users')->where('username', $username)->where('id', '!=', $user->id)->exists()) {
                $username = $base.$i++;
            }
            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable(false)->change();
            $table->dropColumn(['email', 'email_verified_at']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->unique()->nullable()->after('name');
            $table->timestamp('email_verified_at')->nullable()->after('email');
        });

        DB::table('users')->orderBy('id')->get()->each(function ($user) {
            DB::table('users')->where('id', $user->id)->update(['email' => $user->username.'@crxfarm.local']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
