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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('pages')->cascadeOnDelete();
            // `slug` is the single-segment key the API looks pages up by; `path` is the
            // full front-end route from the blueprint (e.g. /nuestros-servicios/gnc).
            $table->string('slug');
            $table->string('path');
            $table->string('title');
            $table->unsignedSmallInteger('position')->default(1);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->unique(['site_id', 'slug']);
            $table->unique(['site_id', 'path']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
