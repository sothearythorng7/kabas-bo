<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'locale',
        'pin_code',
        'store_id',
        'phone',
        'address',
        'hire_date',
        'is_staff',
        'contract_status',
        'contract_end_date',
        'termination_reason',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function documents()
    {
        return $this->hasMany(UserDocument::class);
    }

    public function salaries()
    {
        return $this->hasMany(UserSalary::class)->orderByDesc('effective_from');
    }

    public function currentSalary()
    {
        return $this->hasOne(UserSalary::class)
            ->where('effective_from', '<=', now())
            ->orderByDesc('effective_from')
            ->orderByDesc('id');
    }

    public function salaryAdvances()
    {
        return $this->hasMany(SalaryAdvance::class)->orderByDesc('requested_at');
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class)->orderByDesc('start_date');
    }

    public function schedules()
    {
        return $this->hasMany(UserSchedule::class)->orderBy('day_of_week');
    }

    public function salaryPayments()
    {
        return $this->hasMany(SalaryPayment::class)->orderByDesc('period');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'hire_date' => 'date',
        ];
    }
}
