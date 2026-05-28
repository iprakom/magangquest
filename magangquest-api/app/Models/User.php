<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'onboarding_status',
        'google_id',
        'avatar',
        'nip',
        'unit_kerja',
        'intern_type',
        'start_date',
        'end_date',
        'document_path',
        'room',
        'mentor_id',
        'is_critical_zone',
        'is_grace_period',
        'grace_period_started_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_critical_zone' => 'boolean',
            'is_grace_period' => 'boolean',
            'grace_period_started_at' => 'datetime',
        ];
    }

    // Role constants
    const ROLE_PLAYER = 'player';
    const ROLE_MENTOR = 'mentor';
    const ROLE_ADMIN = 'admin';

    // Onboarding status constants
    const ONBOARDING_RESTRICTED = 'restricted';
    const ONBOARDING_PENDING = 'pending';
    const ONBOARDING_ACTIVE = 'active';
    const ONBOARDING_FROZEN = 'frozen';

    // Intern type constants
    const TYPE_SMA_SMK = 'sma_smk';
    const TYPE_MAHASISWA = 'mahasiswa';
    const TYPE_PROFESIONAL = 'profesional';

    // Relationships
    public function questsCreated(): HasMany
    {
        return $this->hasMany(Quest::class, 'created_by');
    }

    public function questAssignments(): HasMany
    {
        return $this->hasMany(QuestAssignment::class);
    }

    public function streak(): HasOne
    {
        return $this->hasOne(Streak::class);
    }

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    // Helper methods
    public function isPlayer(): bool
    {
        return $this->role === self::ROLE_PLAYER;
    }

    public function isMentor(): bool
    {
        return $this->role === self::ROLE_MENTOR;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isActive(): bool
    {
        return $this->onboarding_status === self::ONBOARDING_ACTIVE;
    }

    public function isRestricted(): bool
    {
        return $this->onboarding_status === self::ONBOARDING_RESTRICTED;
    }

    // WIP Slot calculations
    public function getMaxSlotCapacity(): int
    {
        $globalLimit = SystemSetting::get('global_limit', 4);
        $multiplier = SystemSetting::get('slot_multiplier', 4);

        return $globalLimit * $multiplier;
    }

    public function getUsedSlots(): int
    {
        return $this->questAssignments()
            ->active()
            ->sum('slot_consumed');
    }

    public function getAvailableSlots(): int
    {
        return $this->getMaxSlotCapacity() - $this->getUsedSlots();
    }

    public function getSlotUtilization(): float
    {
        $max = $this->getMaxSlotCapacity();
        if ($max === 0) return 0;

        return ($this->getUsedSlots() / $max) * 100;
    }

    public function getSlotStatus(): string
    {
        $utilization = $this->getSlotUtilization();

        if ($utilization >= 100) return 'overloaded';
        if ($utilization <= 50) return 'idle';
        return 'optimal';
    }

    // Points
    public function getCurrentPoints(): int
    {
        $lastTransaction = $this->pointTransactions()
            ->orderBy('id', 'desc')
            ->first();

        return $lastTransaction?->balance_after ?? 0;
    }

    // Days remaining (calendar days)
    public function getDaysRemaining(): ?int
    {
        if (!$this->end_date) return null;

        return max(0, now()->startOfDay()->diffInDays($this->end_date, false));
    }

    // Working days remaining (excluding weekends and holidays)
    public function getWorkingDaysRemaining(): ?int
    {
        if (!$this->end_date) return null;

        $today = now()->startOfDay();
        $endDate = $this->end_date->startOfDay();

        // If end date is in the past, return negative
        if ($endDate < $today) {
            return -$this->countWorkingDaysPast($endDate, $today);
        }

        return $this->countWorkingDaysRemaining($today, $endDate);
    }

    protected function countWorkingDaysRemaining(\Carbon\Carbon $start, \Carbon\Carbon $end): int
    {
        $count = 0;
        $current = $start->copy();
        $current->addDay(); // Start from tomorrow

        $holidays = \App\Models\Holiday::whereBetween('date', [$current->format('Y-m-d'), $end->format('Y-m-d')])
            ->pluck('date')
            ->map(fn($d) => $d->startOfDay()->timestamp)
            ->toArray();

        while ($current <= $end) {
            $dayOfWeek = $current->dayOfWeek;
            $currentTime = $current->timestamp;

            // Weekday (1-5 = Monday-Friday)
            $isWeekday = $dayOfWeek >= 1 && $dayOfWeek <= 5;
            // Not a holiday
            $isNotHoliday = !in_array($currentTime, $holidays);

            if ($isWeekday && $isNotHoliday) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }

    protected function countWorkingDaysPast(\Carbon\Carbon $end, \Carbon\Carbon $today): int
    {
        $count = 0;
        $current = $end->copy();
        $current->addDay(); // Start from day after end

        $holidays = \App\Models\Holiday::whereBetween('date', [$current->format('Y-m-d'), $today->format('Y-m-d')])
            ->pluck('date')
            ->map(fn($d) => $d->startOfDay()->timestamp)
            ->toArray();

        while ($current < $today) {
            $dayOfWeek = $current->dayOfWeek;
            $currentTime = $current->timestamp;

            $isWeekday = $dayOfWeek >= 1 && $dayOfWeek <= 5;
            $isNotHoliday = !in_array($currentTime, $holidays);

            if ($isWeekday && $isNotHoliday) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }

    public function isInCriticalZone(): bool
    {
        $workingDays = $this->getWorkingDaysRemaining();
        if ($workingDays === null) return false;

        $criticalDays = SystemSetting::get('critical_zone_days', 10);
        return $workingDays <= $criticalDays && $workingDays >= 0;
    }

    public function isInGraduationPhase(): bool
    {
        $workingDays = $this->getWorkingDaysRemaining();
        if ($workingDays === null) return false;

        return $workingDays <= 0;
    }

    public function isInGracePeriod(): bool
    {
        return $this->is_grace_period;
    }
}
