<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory;
    use SoftDeletes;

    // protected $hidden = [ 'id', 'id_vehicle_bp', 'created_at', 'updated_at', 'deleted_at'];

    protected $fillable = [
        'id_vehicle_bp',
        'name',
        'vin',
        'description',
        'model',
        'brand',
        'body',
        'km',
        'plates',
        'price',
        'purchase_date',
        'year_model',
        'cylinders',
        'exterior_color',
        'interior_color',
        'transmission',
        'drive_train',
        'location',
    ];

    public function customers()
    {
        return $this->belongsToMany(Customer::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}