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
        Schema::table('parachute_inspections', function (Blueprint $table) {
            $table->bigInteger('updated_by')->nullable()->after('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parachute_inspections', function (Blueprint $table) {
            $table->dropColumn('updated_by');
        });
    }
};
