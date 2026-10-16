<?php

namespace Tests\Unit;

use App\Support\QuantityFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class QuantityFormatterTest extends TestCase
{
    #[DataProvider('quantityValues')]
    public function test_it_removes_insignificant_decimal_zeroes(string|float|int $value, string $expected): void
    {
        $this->assertSame($expected, QuantityFormatter::format($value));
    }

    /**
     * @return array<string, array{string|float|int, string}>
     */
    public static function quantityValues(): array
    {
        return [
            'whole quantity' => ['20000.000', '20,000'],
            'fractional quantity' => ['20000.500', '20,000.5'],
            'three digit precision' => [25.125, '25.125'],
            'zero' => [0, '0'],
            'negative movement' => [-300.000, '-300'],
        ];
    }
}
