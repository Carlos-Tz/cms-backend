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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            // Derivable from the category, stored anyway: every domain table
            // carries `site_id`. Same call as `appointments.site_id`.
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            // ponytail: restrict, not cascade. Deleting a category must not take
            // its products down in silence; the controller turns it into a 409.
            $table->foreignId('product_category_id')->constrained()->restrictOnDelete();
            $table->string('slug');
            $table->string('name');
            $table->text('description');
            // ponytail: null = «precio a cotizar». Industrial equipment is quoted
            // more often than listed. No currency column: everything is MXN.
            $table->decimal('price', 12, 2)->nullable();
            // ponytail: an ordered list of media paths, the same shape as the
            // collections inside `sections.content`. A `product_images` table
            // pays off only when each image needs its own `alt` and `caption`.
            $table->json('images');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['site_id', 'slug']);
            $table->index(['site_id', 'product_category_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
