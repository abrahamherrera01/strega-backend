<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSurveysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->string('satisfaction')->nullable();
            $table->integer('csi')->nullable();
            $table->integer('nps')->nullable();
            $table->integer('recomendation')->nullable();
            $table->integer('efficiency')->nullable();
            $table->integer('advisor')->nullable();
            $table->integer('job')->nullable();
            $table->text('comments')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('restrict')->onUpdate('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('surveys');
    }
}
