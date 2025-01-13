<?php

namespace App;

class Helpers
{
    public static function formatGermanDate(\DateTime $dateTime): string
    {
        $months = [
            '01' => 'Januar',
            '02' => 'Februar',
            '03' => 'März',
            '04' => 'April',
            '05' => 'Mai',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'August',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Dezember',
        ];

        $day = $dateTime->format('d');
        $monthNum = $dateTime->format('m');
        $year = $dateTime->format('Y');

        $monthName = $months[$monthNum] ?? $monthNum;
        return "{$day}. {$monthName} {$year}";
    }

    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }
}
