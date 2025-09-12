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
        'amount',
        'description',
        'reference',
        'document_path',
        'date',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
