<?php

namespace App\Models;
use App\Models\VariationValue;

use Illuminate\Database\Eloquent\Model;

class VariationType extends Model
{
    protected $fillable = ['name', 'label'];

    public function values()
    {
        return $this->hasMany(VariationValue::class);
    }
}
