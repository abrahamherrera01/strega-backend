<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVentaTempsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('venta_temps', function (Blueprint $table) {
            $table->id();
            $table->string('fecha_entrega')->nullable();
            $table->string('mes')->nullable();
            $table->string('tipo_venta')->nullable();
            $table->string('sucursal')->nullable();
            $table->string('modelo')->nullable();
            $table->string('vin')->nullable();
            $table->string('ejecutivo')->nullable();
            $table->string('cliente')->nullable();
            $table->string('numero')->nullable();
            $table->string('numero_2')->nullable();
            $table->string('email')->nullable();
            $table->string('estatus_crm')->nullable();
            $table->string('venta_registrada_crm')->nullable();
            $table->integer('nps')->nullable();
            $table->string('incidencia')->nullable();
            $table->text('comentarios')->nullable();
            $table->integer('intentos')->nullable();
            $table->string('estatus')->nullable();
            $table->string('motivo_no_contacto')->nullable();
            $table->string('correo_correcto')->nullable();
            $table->string('medio_contacto')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('correo')->nullable();
            $table->string('area_1')->nullable();
            $table->string('tipo_queja')->nullable();
            $table->string('area_2')->nullable();
            $table->string('tipo_queja_2')->nullable();
            $table->text('comentario')->nullable();
            $table->text('sugerencia')->nullable();
            $table->string('solicitud')->nullable();
            $table->string('felicitacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('venta_temps');
    }
}
