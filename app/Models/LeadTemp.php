<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadTemp extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'numero','fecha_hora_llegada','mes','seguimiento_anterior','intento_transferencia','fuente',
        'tipo','campaña','id_oportunidad','nombre','apellido','telefono_1','telefono_2','correo','ubicacion',
        'comentario_prospecto','unidad','inversion','consultor','medio_contacto','departamento','ejecutivo',
        'fecha','asignado','enlazado','contactado','resultado','observaciones','estatus','actividad_a_realizar',
        'llamada','plantilla','estatus_respuesta','fecha_contacto','estatus_crm','comentario_seguimiento',
        'cita_valuacion','fecha_cita_valuacion','hora_cita_valuacion','estatus_cita_valuacion','compra_auto_nuevo',
        'compra_directa','anio_modelo','cita_normal','fecha_cita_normal','hora_cita_normal','estatus_cita_normal','compra',
        'consultor_experiencia','fecha_experiencia','estatus_experiencia','estatus_encuestado','queja_ticket',
        'incidencia','comentario_experiencia','calificacion_csi','satisfaccion','medio_contacto_experiencia',
        'motivo_venta_perdida','segunda_incidencia','fecha_toma','tiempo_atencion'
    ];
}
