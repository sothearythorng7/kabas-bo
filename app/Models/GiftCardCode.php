<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class GiftCardCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'gift_card_id',
        'code',
        'original_amount',
        'remaining_amount',
        'is_active',
        'used_at',
        'order_id',
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'used_at' => 'datetime',
    ];

    // Relations
    public function giftCard()
    {
        return $this->belongsTo(GiftCard::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Génération automatique du code
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($code) {
            if (!$code->code) {
                $code->code = static::generateUniqueCode();
            }
        });
    }

    // Génération d'un code unique
    public static function generateUniqueCode()
    {
        do {
            // Format: GIFT-XXXX-XXXX-XXXX
            $code = 'GIFT-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    // Vérifier si le code est utilisable
    public function isUsable()
    {
        return $this->is_active && $this->remaining_amount > 0;
    }

    // Utiliser partiellement ou totalement le code
    public function use($amount)
    {
        if ($amount > $this->remaining_amount) {
            throw new \Exception('Amount exceeds remaining balance');
        }

        $this->remaining_amount -= $amount;

        if ($this->remaining_amount <= 0) {
            $this->remaining_amount = 0;
            $this->is_active = false;
            $this->used_at = now();
        }

        $this->save();

        return $this;
    }
}
