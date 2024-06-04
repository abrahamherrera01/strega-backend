<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServicioTemp extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tipo_orden', 'orden', 'fecha', 'mes','nombre', 'estatus_crm',
        'correo', 'telefono_1', 'telefono_2', 'asesor', 'modelo',
        'serie', 'recomendacion', 'incidencia', 'comentarios',
        'intentos', 'estatus', 'motivo_no_contactado',
        'correo_electronico_correcto', 'medio_contacto', 'whatsapp',
        'correo_electronico', 'area_1', 'tipo_queja', 'area_2',
        'tipo_queja_2', 'comentario', 'sugerencia', 'solicitud',
        'felicitacion'
    ];
}
