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
        Schema::table('parachute_inspection_items', function (Blueprint $table) {
            $table->dateTime('status_date')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parachute_inspection_items', function (Blueprint $table) {
            $table->dropColumn('status_date');
        });
    }
};
