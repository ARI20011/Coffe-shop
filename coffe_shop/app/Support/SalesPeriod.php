<?php

namespace App\Support;

use Carbon\Carbon;

class SalesPeriod
{
    public const OPTIONS = [
        'hari' => 'Hari ini',
        'minggu' => 'Minggu ini',
        'bulan' => 'Bulan ini',
    ];

    /**
     * @return array{Carbon, Carbon}
     */
    public static function range(string $period): array
    {
        $end = now();
        $start = match ($period) {
            'hari' => $end->copy()->startOfDay(),
            'minggu' => $end->copy()->startOfWeek(Carbon::MONDAY),
            default => $end->copy()->startOfMonth(),
        };

        return [$start, $end];
    }
}
