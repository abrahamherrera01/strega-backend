<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Incidence extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
    ];

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function areas()
    {
        return $this->belongsToMany(Area::class);
    }
}
