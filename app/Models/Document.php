<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'documentable_type',
        'documentable_id',
        'file_path',
        'name',
        'type', // optional: invoice, receipt, general, etc.
    ];

    public function documentable()
    {
        return $this->morphTo();
    }

    public function getUrlAttribute()
    {
        return \Storage::url($this->file_path);
    }
}
