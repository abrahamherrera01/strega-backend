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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('id_vehicle_bp')->nullable();
            $table->string('name')->nullable();
            $table->string('vin');
            $table->text('description')->nullable();
            $table->string('model')->nullable();
            $table->string('brand')->nullable();
            $table->string('body')->nullable();
            $table->unsignedBigInteger('km')->nullable();
            $table->string('plates')->nullable();
            $table->double('price', 15, 3)->nullable();
            $table->dateTime('purchase_date')->nullable();
            $table->unsignedBigInteger('year_model')->nullable();
            $table->unsignedBigInteger('cylinders')->nullable();
            $table->string('exterior_color')->nullable();
            $table->string('interior_color')->nullable();
            $table->string('transmission')->nullable();
            $table->string('drive_train')->nullable();
            $table->string('location')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};