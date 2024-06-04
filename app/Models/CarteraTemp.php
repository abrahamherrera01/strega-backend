<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CarteraTemp extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tipo', 'fecha', 'forma_contacto', 'medio_contacto', 'submedio_contacto',
        'nombre_cliente', 'telefono', 'correo', 'producto', 'consultor', 'ejecutivo',
        'fecha_estatus', 'estatus', 'departamento', 'fecha_llamada', 'estatus_contactado',
        'motivo_no_encuesta', 'queja_ticket', 'incidencia', 'comentario_validacion',
        'calificacion_csi', 'satisfaccion', 'medio_contactado', 'motivo_venta_perdida',
        'actualizacion_datos', 'comentario_seguimiento', 'fecha_cita_valuacion',
        'hora_cita_valuacion', 'toma_compra', 'cita_programada', 'fecha_cita_programada',
        'hora_cita_programada', 'cita_asistida', 'oferta_comercial', 'ofrecimiento_test_drive',
        'test_drive_realizado', 'solicitudes_ingresadas', 'solicitudes_aprobadas',
        'enganche_apartado', 'estatus_compra', 'entregada', 'reportadas_planta_dcs',
        'mes', 'marca'
    ];
}
