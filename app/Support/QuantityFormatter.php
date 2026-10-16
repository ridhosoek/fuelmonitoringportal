<?php

namespace App\Support;

final class QuantityFormatter
{
    public static function format(int|float|string $value): string
    {
        $formatted = number_format((float) $value, 3, '.', ',');

        return rtrim(rtrim($formatted, '0'), '.');
    }

    public static function formatIndonesian(int|float|string $value, int $maximumDecimals = 3): string
    {
        $formatted = number_format((float) $value, $maximumDecimals, ',', '.');

        return rtrim(rtrim($formatted, '0'), ',');
    }

    public static function percentIndonesian(int|float|string $value, int $maximumDecimals = 1): string
    {
        return self::formatIndonesian($value, $maximumDecimals).'%';
    }
}
