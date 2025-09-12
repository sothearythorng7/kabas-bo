<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'type', // 'in' ou 'out'
        'account_id',
        'amount',
        'description',
        'reference',
        'document_path',
        'date',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];


    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function account()
    {
        return $this->hasOne(Account::class, 'id', 'account_id');
    } 
}
