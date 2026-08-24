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
            // Values of the fields a form declares beyond the four every form
            // has. A commercial form asks for its own data — the vehicle to
            // convert, the size of a fleet — and without this they end up
            // buried in the free-text message.
            //
            // JSON and not one column per field: the set is declared per
            // section in `fields[]`, so a new form must not need a migration.
            // What keeps it honest is that the endpoint validates the payload
            // against that `fields[]` and rejects anything undeclared.
            $table->json('details')->nullable()->after('message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('details');
        });
    }
};
