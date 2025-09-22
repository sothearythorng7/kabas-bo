<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = ['supplier_id', 'last_name', 'first_name', 'email', 'phone', 'telegram'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
