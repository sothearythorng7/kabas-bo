<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ReviewInvitation extends Model
{
    protected $fillable = [
        'order_id',
        'customer_email',
        'token',
        'language',
        'sent_at',
        'opened_at',
        'used_at',
        'reminder_sent_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'opened_at' => 'datetime',
            'used_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(WebsiteOrder::class, 'order_id');
    }

    public static function generateToken(): string
    {
        do {
            $token = Str::random(48);
        } while (self::where('token', $token)->exists());
        return $token;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function markUsed(): void
    {
        $this->update(['used_at' => now()]);
    }

    public function markOpened(): void
    {
        if ($this->opened_at === null) {
            $this->update(['opened_at' => now()]);
        }
    }
}
