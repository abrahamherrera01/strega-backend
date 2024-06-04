<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory;
    use SoftDeletes;

    // protected $hidden = [ 'id', 'created_at', 'updated_at', 'deleted_at'];

    protected $fillable = [
        'id_client_bp',
        'rfc',
        'tax_regime',
        'full_name',
        'gender',
        'contact_method',
        'phone_1',
        'phone_2',
        'phone_3',
        'cellphone',
        'email_1',
        'email_2',
        'city',
        'delegacy',
        'colony',
        'address',
        'zip_code',
        'type',
        'picture',
    ];

    public function vehicles()
    {
        return $this->belongsToMany(Vehicle::class);
    }

    public function vehiclesWithOrders()
    {
        return $this->belongsToMany(Vehicle::class)
            ->with(['orders.incidence', 'orders.survey'])
            ->get();
    }

    public function vehiclesWithSaleOrders()
    {
        return $this->belongsToMany(Vehicle::class)
        ->whereHas('orders', function($query) {
            $query->where('order_type', 'Sale');
        })
        ->with(['orders' => function($query) {
            $query->where('order_type', 'Sale')
                  ->with('incidence', 'survey');
        }])
        ->get();
    }


    public function vehiclesWithAftersaleOrders()
    {
        return $this->belongsToMany(Vehicle::class)
        ->whereHas('orders', function($query) {
            $query->where('order_type', 'Aftersale');
        })
        ->with(['orders' => function($query) {
            $query->where('order_type', 'Aftersale')
                  ->with('incidence', 'survey');
        }])
        ->get();
    }


    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function orders_fact()
    {
        return $this->hasMany(Order::class, 'customer_fact_id', 'id');
    }

    public function orders_contact()
    {
        return $this->hasMany(Order::class, 'customer_contact_id', 'id');
    }

    public function orders_legal()
    {
        return $this->hasMany(Order::class, 'customer_legal_id', 'id');
    }

    public function sum_orders()
    {
        return $this->hasMany(Order::class);
    }

    public function vehicleOrders()
    {
        return Order::whereHas('vehicle', function ($query) {
            $query->whereHas('customers', function ($query) {
                $query->where('customer_id', $this->id);
            });
        });
    }

}