<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Survey extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'csi',
        'nps',
        'recomendation',
        'efficiency',
        'advisor',
        'job',
        'comments'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
