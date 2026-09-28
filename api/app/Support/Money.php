<?php

namespace App\Support;

class Money
{
    public static function toCents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    public static function fromCents(int $cents): float
    {
        return round($cents / 100, 2);
    }

    public static function add(int ...$cents): int
    {
        return array_sum($cents);
    }

    public static function mul(int $cents, int $factor): int
    {
        return (int) round($cents * $factor);
    }

    public static function div(int $cents, int $divisor): int
    {
        if ($divisor === 0) {
            throw new \InvalidArgumentException('Division by zero in Money.');
        }

        return (int) round($cents / $divisor);
    }

    public static function roundHalfUp(int $numerator, int $denominator): int
    {
        if ($denominator === 0) {
            throw new \InvalidArgumentException('Division by zero in Money.');
        }

        $sign = ($numerator < 0) !== ($denominator < 0) ? -1 : 1;
        $absNum = abs($numerator);
        $absDen = abs($denominator);

        return $sign * intdiv($absNum * 2 + $absDen, $absDen * 2);
    }
}
