<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;

class AcademicYear
{
    /**
     * Năm học Việt Nam bắt đầu từ tháng 8 và kết thúc tháng 7 năm sau.
     * VD: 15/09/2026 -> "2026-2027", 15/03/2027 -> "2026-2027".
     */
    public static function forDate(DateTimeInterface $date): string
    {
        $year = (int) $date->format('Y');
        $month = (int) $date->format('n');

        return $month >= 8 ? "{$year}-".($year + 1) : ($year - 1)."-{$year}";
    }

    /**
     * Khoảng thời gian [đầu tháng 8 -> cuối tháng 7 năm sau] của một năm học dạng "2026-2027".
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function dateRange(string $academicYear): array
    {
        $startYear = (int) strtok($academicYear, '-');

        return [
            Carbon::create($startYear, 8, 1, 0, 0, 0),
            Carbon::create($startYear + 1, 7, 31, 23, 59, 59),
        ];
    }

    /**
     * Thứ tự tháng trong năm học (tháng 8 là tháng đầu tiên, tháng 7 năm sau là tháng cuối).
     */
    public static function monthOrder(int $month): int
    {
        return $month >= 8 ? $month - 8 : $month + 4;
    }
}
