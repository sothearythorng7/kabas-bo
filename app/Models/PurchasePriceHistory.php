<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchasePriceHistory extends Model
{
    protected $fillable = ['supplier_id','product_id','old_price','new_price','changed_at'];

    public $timestamps = false;

    protected $dates = ['changed_at'];
}
