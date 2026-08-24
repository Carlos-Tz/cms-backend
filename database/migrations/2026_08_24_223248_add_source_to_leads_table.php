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
        Schema::table('leads', function (Blueprint $table) {
            // Slug of the page whose form produced the lead. A site carries
            // several `lead_capture_form` sections — gnu-vehicular has four —
            // and without this the admin cannot tell a quote request from a
            // generic contact. Nullable: a front that does not send it still works.
            $table->string('source')->nullable()->after('site_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
