<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeadsTempTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lead_temps', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->nullable();
            $table->string('fecha_hora_llegada')->nullable();
            $table->string('mes')->nullable();
            $table->string('seguimiento_anterior')->nullable();
            $table->string('intento_transferencia')->nullable();
            $table->string('fuente')->nullable();
            $table->string('tipo')->nullable();
            $table->string('campaña')->nullable();
            $table->string('id_oportunidad')->nullable();
            $table->string('nombre')->nullable();
            $table->string('apellido')->nullable();
            $table->string('telefono_1')->nullable();
            $table->string('telefono_2')->nullable();
            $table->string('correo')->nullable();
            $table->string('ubicacion')->nullable();
            $table->string('comentario_prospecto')->nullable();
            $table->string('unidad')->nullable();
            $table->string('inversion')->nullable();
            $table->string('consultor')->nullable();
            $table->string('medio_contacto')->nullable();
            $table->string('departamento')->nullable();
            $table->string('ejecutivo')->nullable();
            $table->string('fecha')->nullable();
            $table->string('asignado')->nullable();
            $table->string('enlazado')->nullable();
            $table->string('contactado')->nullable();
            $table->string('resultado')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('estatus')->nullable();
            $table->string('actividad_a_realizar')->nullable();
            $table->string('llamada')->nullable();
            $table->string('plantilla')->nullable();
            $table->string('estatus_respuesta')->nullable();
            $table->string('fecha_contacto')->nullable();
            $table->string('estatus_crm')->nullable();
            $table->text('comentario_seguimiento')->nullable();
            $table->string('cita_valuacion')->nullable();
            $table->string('fecha_cita_valuacion')->nullable();
            $table->string('hora_cita_valuacion')->nullable();
            $table->string('estatus_cita_valuacion')->nullable();
            $table->string('compra_auto_nuevo')->nullable();
            $table->string('compra_directa')->nullable();
            $table->string('anio_modelo')->nullable();
            $table->string('cita_normal')->nullable();
            $table->string('fecha_cita_normal')->nullable();
            $table->string('hora_cita_normal')->nullable();
            $table->string('estatus_cita_normal')->nullable();
            $table->string('compra')->nullable();
            $table->string('consultor_experiencia')->nullable();
            $table->string('fecha_experiencia')->nullable();
            $table->string('estatus_experiencia')->nullable();
            $table->string('estatus_encuestado')->nullable();
            $table->string('queja_ticket')->nullable();
            $table->string('incidencia')->nullable();
            $table->text('comentario_experiencia')->nullable();
            $table->string('calificacion_csi')->nullable();
            $table->string('satisfaccion')->nullable();
            $table->string('medio_contacto_experiencia')->nullable();
            $table->string('motivo_venta_perdida')->nullable();
            $table->string('segunda_incidencia')->nullable();
            $table->string('fecha_toma')->nullable();
            $table->string('tiempo_atencion')->nullable();
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
        Schema::dropIfExists('lead_temps');
    }
}
