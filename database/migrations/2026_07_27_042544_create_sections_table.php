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
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            // Null page_id marks a global section (site-wide), e.g. contact_info.
            $table->foreignId('page_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->unsignedSmallInteger('position')->default(1);
            $table->json('content');
            $table->timestamps();

            $table->index(['site_id', 'page_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
