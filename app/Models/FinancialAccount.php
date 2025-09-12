<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\FinancialAccountType;

class FinancialAccount extends Model
{
    protected $fillable = ['code', 'name', 'type', 'parent_id'];

    protected $casts = [
        'type' => FinancialAccountType::class,
    ];

    public function parent()
    {
        return $this->belongsTo(FinancialAccount::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(FinancialAccount::class, 'parent_id');
    }

    public function transactions()
    {
        return $this->hasMany(FinancialTransaction::class, 'account_id');
    }
}
