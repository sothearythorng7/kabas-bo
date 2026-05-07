<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbandonedCartSetting extends Model
{
    protected $fillable = [
        'enabled', 'discount_type', 'discount_value', 'validity_days', 'promotion_rule_id',
        'subject', 'heading', 'intro_body', 'cta_label', 'footer_text',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'discount_value' => 'decimal:2',
        'validity_days' => 'integer',
        'subject' => 'array',
        'heading' => 'array',
        'intro_body' => 'array',
        'cta_label' => 'array',
        'footer_text' => 'array',
    ];

    public static function current(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            ['enabled' => false, 'discount_type' => 'percent', 'discount_value' => 10, 'validity_days' => 7]
        );
    }

    public function promotionRule()
    {
        return $this->belongsTo(\App\Models\PromotionRule::class, 'promotion_rule_id');
    }
}
