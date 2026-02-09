<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UserSchedule extends Model
{
    protected $fillable = [
        'staff_member_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_working_day',
    ];

    protected $casts = [
        'is_working_day' => 'boolean',
    ];

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }

    public function getDayName(): string
    {
        $days = [
            0 => __('messages.staff.weekdays.sunday'),
            1 => __('messages.staff.weekdays.monday'),
            2 => __('messages.staff.weekdays.tuesday'),
            3 => __('messages.staff.weekdays.wednesday'),
            4 => __('messages.staff.weekdays.thursday'),
            5 => __('messages.staff.weekdays.friday'),
            6 => __('messages.staff.weekdays.saturday'),
        ];

        return $days[$this->day_of_week] ?? '';
    }

    public function getHoursWorked(): float
    {
        if (!$this->is_working_day || !$this->start_time || !$this->end_time) {
            return 0;
        }

        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        return $start->floatDiffInHours($end);
    }
}
