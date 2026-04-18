<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSalary extends Model
{
    protected $fillable = [
        'staff_member_id',
        'base_salary',
        'currency',
        'effective_from',
        'created_by',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'base_salary' => 'decimal:5',
    ];

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
