<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateForeignKeyRestrictionsInCategoryIncidenceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('category_incidence', function (Blueprint $table) {

            $table->dropForeign(['incidence_id']);
            $table->dropForeign(['category_id']);

            $table->foreign('incidence_id')->references('id')->on('incidences')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('category_incidence', function (Blueprint $table) {

            $table->dropForeign(['incidence_id']);
            $table->dropForeign(['category_id']);

            $table->foreign('incidence_id')->references('id')->on('incidences')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }
}

