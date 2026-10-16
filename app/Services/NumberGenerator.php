<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class NumberGenerator
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public function next(string $prefix, string $modelClass, string $column, CarbonInterface|string|null $date = null): string
    {
        $numberDate = match (true) {
            $date instanceof CarbonInterface => Carbon::instance($date),
            is_string($date) => Carbon::parse($date),
            default => today(),
        };

        $needle = sprintf('%s-%s-', $prefix, $numberDate->format('Ymd'));
        $sequence = $modelClass::query()
            ->where($column, 'like', $needle.'%')
            ->count() + 1;

        return sprintf('%s%04d', $needle, $sequence);
    }
}
