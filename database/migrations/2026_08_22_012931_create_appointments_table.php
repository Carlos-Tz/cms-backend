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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            // Derivable from the station, but every domain table carries site_id
            // by convention. Filled from the route binding, never the payload.
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            // One of Appointment::SLOTS, stored as its label: the API takes and
            // returns the same string, so there is nothing to translate.
            $table->string('slot');
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->string('vehicle_plate');
            $table->string('vehicle_description')->nullable();
            $table->timestamps();

            // One appointment per slot: the real guard against double booking.
            // The Rule::unique in the form request only makes the 422 readable.
            $table->unique(['station_id', 'date', 'slot']);
            $table->index(['site_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
