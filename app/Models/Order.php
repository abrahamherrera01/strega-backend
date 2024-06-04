<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

    // protected $hidden = [ 'id', 'customer_id', 'vehicle_id', 'sales_executive_id' ,'branch_id', 'created_at', 'updated_at', 'deleted_at'];

    protected $fillable = [
        'id_order_bp',
        'service_date',
        'service_billing_date',
        'sale_billing_date',
        'gross_price',
        'tax_price',
        'total_price',
        'order_km',
        'observations',
        'order_type',
        'order_category',
        'customer_id',
        'customer_fact_id',
        'customer_contact_id',
        'customer_legal_id',
        'vehicle_id',
        'sales_executive_id',
        'branch_id',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function salesExecutive()
    {
        return $this->belongsTo(SalesExecutive::class);
    }

    public function biller()
    {
        return $this->belongsTo(Customer::class, 'customer_fact_id', 'id');
    }

    public function contact()
    {
        return $this->belongsTo(Customer::class, 'customer_contact_id', 'id');
    }

    public function legal()
    {
        return $this->belongsTo(Customer::class, 'customer_legal_id', 'id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function survey()
    {
        return $this->hasOne(Survey::class);
    }

    public function incidence()
    {
        return $this->hasOne(Incidence::class);
    }
}