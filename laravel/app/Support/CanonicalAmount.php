<?php

namespace App\Support;

final class CanonicalAmount
{
    public static function minorUnits(mixed $amount): ?string
    {
        if (is_int($amount)) {
            return $amount.'00';
        }

        if (! is_string($amount) || ! preg_match('/^(0|[1-9][0-9]*)(?:\.([0-9]{1,2}))?$/', $amount, $matches)) {
            return null;
        }

        $fraction = str_pad($matches[2] ?? '', 2, '0');
        $major = ltrim($matches[1], '0');

        return ($major === '' ? '0' : $major).$fraction;
    }

    public static function equals(mixed $left, mixed $right): bool
    {
        $leftMinor = self::minorUnits($left);
        $rightMinor = self::minorUnits($right);

        return $leftMinor !== null && $rightMinor !== null && hash_equals($leftMinor, $rightMinor);
    }
}
