<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoryIncidenceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('category_incidence', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('incidence_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamps();

            $table->foreign('incidence_id')->references('id')->on('incidences')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');

            $table->unique(['incidence_id', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('category_incidence');
    }
}
