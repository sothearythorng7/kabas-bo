<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PopupEvent extends Model
{
    protected $fillable = [
        'reference', 'name', 'location', 'store_id',
        'start_date', 'end_date', 'status', 'notes',
        'created_by_user_id', 'activated_at', 'completed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'activated_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function items()
    {
        return $this->hasMany(PopupEventItem::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    // --- Status helpers ---

    public function isPlanned()
    {
        return $this->status === 'planned';
    }

    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function isEditable()
    {
        return $this->status === 'planned';
    }

    // --- Computed ---

    public function getTotalAllocatedAttribute()
    {
        return $this->items->sum('quantity_allocated');
    }

    public function getTotalSoldAttribute()
    {
        return $this->items->sum('quantity_sold');
    }

    // --- Reference ---

    public static function generateReference(): string
    {
        $year = date('Y');
        $last = static::where('reference', 'like', "EVT-{$year}-%")
            ->orderByRaw("CAST(SUBSTRING_INDEX(reference, '-', -1) AS UNSIGNED) DESC")
            ->first();

        $next = 1;
        if ($last) {
            $parts = explode('-', $last->reference);
            $next = (int) end($parts) + 1;
        }

        return sprintf('EVT-%s-%04d', $year, $next);
    }
}
