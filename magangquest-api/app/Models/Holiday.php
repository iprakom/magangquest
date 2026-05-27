<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'name',
        'type',
        'is_recurring',
    ];

    protected $casts = [
        'date' => 'date',
        'is_recurring' => 'boolean',
    ];

    const TYPE_NATIONAL = 'national';
    const TYPE_LOCAL = 'local';
    const TYPE_COMPANY = 'company';

    // Check if a date is a holiday
    public static function isHoliday(\DateTimeInterface $date): bool
    {
        return self::where('date', $date->format('Y-m-d'))->exists();
    }

    // Check if a date is a working day (not weekend and not holiday)
    public static function isWorkingDay(\DateTimeInterface $date): bool
    {
        $dayOfWeek = (int) $date->format('N');

        // Weekend (6=Saturday, 7=Sunday)
        if ($dayOfWeek >= 6) {
            return false;
        }

        return !self::isHoliday($date);
    }

    // Count working days between two dates
    public static function countWorkingDays(
        \DateTimeInterface $start,
        \DateTimeInterface $end
    ): int {
        $count = 0;
        $current = $start;

        while ($current <= $end) {
            if (self::isWorkingDay($current)) {
                $count++;
            }
            $current = $current->modify('+1 day');
        }

        return $count;
    }

    // Get next working day
    public static function getNextWorkingDay(\DateTimeInterface $date): \DateTime
    {
        $next = $date->modify('+1 day');

        while (!self::isWorkingDay($next)) {
            $next = $next->modify('+1 day');
        }

        return $next;
    }
}
