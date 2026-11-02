<?php

declare(strict_types=1);

namespace App\Support;

final class Money
{
    public static function format(int $cents, ?string $symbol = null): string
    {
        $symbol ??= (string) config('quotecalc.currency_symbol', '£');
        $sign = $cents < 0 ? '−' : '';

        return $sign.$symbol.number_format(abs($cents) / 100, 2, '.', ',');
    }
}
