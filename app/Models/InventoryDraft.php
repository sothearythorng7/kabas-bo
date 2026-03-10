<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryDraft extends Model
{
    protected $fillable = [
        'user_id',
        'location_type',
        'location_id',
        'counts',
    ];

    protected $casts = [
        'counts' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
