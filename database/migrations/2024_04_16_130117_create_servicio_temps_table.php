<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServicioTempsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('servicio_temps', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_orden')->nullable();
            $table->string('orden')->nullable();
            $table->string('fecha')->nullable();
            $table->string('mes')->nullable();
            $table->string('nombre')->nullable();
            $table->string('estatus_crm')->nullable();
            $table->string('correo')->nullable();
            $table->string('telefono_1')->nullable();
            $table->string('telefono_2')->nullable();
            $table->string('asesor')->nullable();
            $table->string('modelo')->nullable();
            $table->string('serie')->nullable();
            $table->string('recomendacion')->nullable();
            $table->string('incidencia')->nullable();
            $table->text('comentarios')->nullable();
            $table->string('intentos')->nullable();
            $table->string('estatus')->nullable();
            $table->string('motivo_no_contactado')->nullable();
            $table->string('correo_electronico_correcto')->nullable();
            $table->string('medio_contacto')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('correo_electronico')->nullable();
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
        Schema::dropIfExists('servicio_temps');
    }
}
