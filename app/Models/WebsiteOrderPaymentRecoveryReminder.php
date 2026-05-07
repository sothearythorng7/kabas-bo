<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteOrderPaymentRecoveryReminder extends Model
{
    protected $table = 'order_payment_recovery_reminders';

    protected $fillable = [
        'order_id',
        'email_sent_to',
        'sent_at',
        'expires_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(WebsiteOrder::class, 'order_id');
    }
}
