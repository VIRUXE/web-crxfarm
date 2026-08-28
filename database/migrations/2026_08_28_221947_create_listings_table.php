<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['part', 'car'])->default('part')->index();
            $table->string('title');
            $table->string('chassis')->nullable()->index(); // CRX, EF, EG, Del Sol, EK, Accord, CRV, ...
            $table->string('price')->nullable(); // free-text: "$160", "$100-150", null = "ask"
            $table->text('description')->nullable();
            $table->text('missing_parts')->nullable(); // for type=car: what's already stripped
            $table->string('location')->nullable();
            $table->string('status')->default('available')->index(); // available, pending, sold
            $table->string('source_marketplace_id')->nullable()->unique(); // links back to scraped listing
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
