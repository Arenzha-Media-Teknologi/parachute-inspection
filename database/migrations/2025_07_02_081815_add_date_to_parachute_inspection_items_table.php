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
            $table->dateTime('date')->nullable()->after('parachute_inspection_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parachute_inspection_items', function (Blueprint $table) {
            $table->dropColumn('date');
        });
    }
};
