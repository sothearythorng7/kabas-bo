<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDocument extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'path',
        'original_name',
        'uploaded_by',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getTypeLabel(): string
    {
        return match($this->type) {
            'photo' => __('messages.staff.document_types.photo'),
            'contract' => __('messages.staff.document_types.contract'),
            'id_card' => __('messages.staff.document_types.id_card'),
            'other' => __('messages.staff.document_types.other'),
            default => $this->type,
        };
    }
}
