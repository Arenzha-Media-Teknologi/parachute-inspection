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
        Schema::create('parachute_inspection_items', function (Blueprint $table) {
            // $table->id();
            $table->bigInteger('id')->autoIncrement();
            $table->bigInteger('parachute_inspection_id');
            $table->string('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('image_file_name')->nullable();
            $table->integer('image_file_size')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // $table->foreign('parachute_inspection_id')->references('id')->on('parachute_inspections')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parachute_inspection_items');
    }
};
