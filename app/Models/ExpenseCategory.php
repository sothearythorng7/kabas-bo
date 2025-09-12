<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'name',
        'description',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
