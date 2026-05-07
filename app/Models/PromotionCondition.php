<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionCondition extends Model
{
    protected $fillable = [
        'promotion_rule_id',
        'type',
        'operator',
        'params',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'params' => 'array',
            'position' => 'integer',
        ];
    }

    public function rule()
    {
        return $this->belongsTo(PromotionRule::class, 'promotion_rule_id');
    }
}
