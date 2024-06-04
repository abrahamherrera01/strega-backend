<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarteraTempsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cartera_temps', function (Blueprint $table) {
            $table->id();
            $table->string('tipo')->nullable();
            $table->string('fecha')->nullable();
            $table->string('forma_contacto')->nullable();
            $table->string('medio_contacto')->nullable();
            $table->string('submedio_contacto')->nullable();
            $table->string('nombre_cliente')->nullable();
            $table->string('telefono')->nullable();
            $table->string('correo')->nullable();
            $table->string('producto')->nullable();
            $table->string('consultor')->nullable();
            $table->string('ejecutivo')->nullable();
            $table->string('fecha_estatus')->nullable();
            $table->string('estatus')->nullable();
            $table->string('departamento')->nullable();
            $table->string('fecha_llamada')->nullable();
            $table->string('estatus_contactado')->nullable();
            $table->string('motivo_no_encuesta')->nullable();
            $table->string('queja_ticket')->nullable();
            $table->string('incidencia')->nullable();
            $table->text('comentario_validacion')->nullable();
            $table->string('calificacion_csi')->nullable();
            $table->string('satisfaccion')->nullable();
            $table->string('medio_contactado')->nullable();
            $table->string('motivo_venta_perdida')->nullable();
            $table->string('actualizacion_datos')->nullable();
            $table->text('comentario_seguimiento')->nullable();
            $table->string('contactado')->nullable();
            $table->string('fecha_cita_valuacion')->nullable();
            $table->string('hora_cita_valuacion')->nullable();
            $table->string('toma_compra')->nullable();
            $table->string('cita_programada')->nullable();
            $table->string('fecha_cita_programada')->nullable();
            $table->string('hora_cita_programada')->nullable();
            $table->string('cita_asistida')->nullable();
            $table->string('oferta_comercial')->nullable();
            $table->string('ofrecimiento_test_drive')->nullable();
            $table->string('test_drive_realizado')->nullable();
            $table->string('solicitudes_ingresadas')->nullable();
            $table->string('solicitudes_aprobadas')->nullable();
            $table->string('enganche_apartado')->nullable();
            $table->string('estatus_compra')->nullable();
            $table->string('entregada')->nullable();
            $table->string('reportadas_planta_dcs')->nullable();
            $table->string('mes')->nullable();
            $table->string('marca')->nullable();
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
        Schema::dropIfExists('cartera_temps');
    }
}
