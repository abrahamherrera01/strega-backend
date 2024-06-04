<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesExecutive extends Model
{
    use HasFactory;
    use SoftDeletes;

    // protected $hidden = [ 'id', 'id_sales_executive_bp', 'user_id', 'branch_id', 'created_at', 'updated_at', 'deleted_at'];

    protected $fillable = [
        'id_sales_executive_bp',
        'name',
        'user_id',
        'branch_id',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}