<?php

namespace App\Util;

class Decimal
{
    public static function add(string|float|int $left, string|float|int $right, int $scale = 2): string
    {
        if (function_exists('bcadd')) {
            return bcadd((string) $left, (string) $right, $scale);
        }

        return self::fromCents(self::toCents($left, $scale) + self::toCents($right, $scale), $scale);
    }

    public static function sub(string|float|int $left, string|float|int $right, int $scale = 2): string
    {
        if (function_exists('bcsub')) {
            return bcsub((string) $left, (string) $right, $scale);
        }

        return self::fromCents(self::toCents($left, $scale) - self::toCents($right, $scale), $scale);
    }

    public static function comp(string|float|int $left, string|float|int $right, int $scale = 2): int
    {
        if (function_exists('bccomp')) {
            return bccomp((string) $left, (string) $right, $scale);
        }

        $leftCents = self::toCents($left, $scale);
        $rightCents = self::toCents($right, $scale);

        if ($leftCents === $rightCents) {
            return 0;
        }

        return $leftCents > $rightCents ? 1 : -1;
    }

    public static function mul(string|float|int $left, string|float|int $right, int $scale = 2): string
    {
        if (function_exists('bcmul')) {
            return bcmul((string) $left, (string) $right, $scale);
        }

        $right = (string) $right;

        if ($right === '1' || $right === '1.00' || $right === '+1' || $right === '+1.00') {
            return self::fromCents(self::toCents($left, $scale), $scale);
        }

        if ($right === '-1' || $right === '-1.00' || $right === '-1.0') {
            return self::fromCents(-self::toCents($left, $scale), $scale);
        }

        $result = (float) $left * (float) $right;
        return number_format($result, $scale, '.', '');
    }

    private static function toCents(string|float|int $value, int $scale = 2): int
    {
        $value = trim((string) $value);
        $negative = false;

        if ($value === '') {
            return 0;
        }

        if (str_starts_with($value, '-') || str_starts_with($value, '+')) {
            $negative = $value[0] === '-';
            $value = substr($value, 1);
        }

        [$int, $dec] = array_pad(explode('.', $value, 2), 2, '');
        $dec = substr(str_pad($dec, $scale, '0'), 0, $scale);

        $cents = (int) ltrim($int . $dec, '0');

        return $negative ? -$cents : $cents;
    }

    private static function fromCents(int $cents, int $scale): string
    {
        $negative = $cents < 0;
        $cents = abs($cents);

        $padded = str_pad((string) $cents, $scale + 1, '0', STR_PAD_LEFT);
        $intPart = substr($padded, 0, -$scale) ?: '0';
        $decPart = substr($padded, -$scale);

        return ($negative ? '-' : '') . $intPart . '.' . $decPart;
    }
}
