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
        Schema::create('parachute_inspection_item_descriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parachute_inspection_item_id');
            $table->string('description', 500)->nullable();
            $table->string('type', 50)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parachute_inspection_item_descriptions');
    }
};
