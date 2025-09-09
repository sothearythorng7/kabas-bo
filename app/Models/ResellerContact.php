<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResellerContact extends Model
{
    use HasFactory;

    protected $fillable = ['reseller_id', 'name', 'email', 'phone'];

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }
}
