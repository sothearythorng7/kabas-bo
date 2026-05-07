<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentRecoverySetting extends Model
{
    protected $fillable = [
        'enabled',
        'delay_hours',
        'link_validity_days',
        'subject',
        'heading',
        'intro_body',
        'cta_label',
        'footer_text',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'delay_hours' => 'integer',
        'link_validity_days' => 'integer',
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
            [
                'enabled' => false,
                'delay_hours' => 24,
                'link_validity_days' => 7,
            ]
        );
    }
}
