<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{

    protected $fillable = ['id', 'customer_name', 'total_price', 'product_id', 'product_name'];


}
