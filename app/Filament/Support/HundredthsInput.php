<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Filament\Forms\Components\TextInput;

/**
 * Edits an integer stored in hundredths (cents or basis points) as a human decimal:
 * 4500 cents ⇄ "45.00" pounds, 1500 bps ⇄ "15.00" percent.
 */
final class HundredthsInput
{
    public static function make(string $name): TextInput
    {
        return TextInput::make($name)
            ->numeric()
            ->minValue(0)
            ->step(0.01)
            ->required()
            ->formatStateUsing(static fn (mixed $state): ?string => is_numeric($state) ? number_format(((int) $state) / 100, 2, '.', '') : null)
            ->dehydrateStateUsing(static fn (mixed $state): int => (int) round(((float) $state) * 100));
    }

    public static function money(string $name): TextInput
    {
        return self::make($name)->prefix((string) config('quotecalc.currency_symbol'));
    }

    public static function percent(string $name): TextInput
    {
        return self::make($name)->suffix('%')->maxValue(100);
    }
}
