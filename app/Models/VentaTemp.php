<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VentaTemp extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'fecha_entrega', 'mes', 'tipo_venta', 'sucursal', 'modelo', 'vin', 'ejecutivo',
        'cliente', 'numero', 'numero_2', 'email', 'estatus_crm',
        'venta_registrada_crm', 'nps', 'incidencia', 'comentarios',
        'intentos', 'estatus', 'motivo_no_contacto', 'correo_correcto',
        'medio_contacto', 'whatsapp', 'correo', 'area_1', 'tipo_queja',
        'area_2', 'tipo_queja_2', 'comentario', 'sugerencia',
        'solicitud', 'felicitacion'
    ];
}
