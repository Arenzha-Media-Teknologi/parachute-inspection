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
        Schema::create('parachute_inspections', function (Blueprint $table) {
            // $table->id();
            $table->bigInteger('id')->autoIncrement();
            $table->date('date');
            $table->string('activity_name')->nullable();
            $table->string('person_in_charge')->nullable();
            $table->bigInteger('parachute_id');
            $table->bigInteger('created_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parachute_inspections');
    }
};
