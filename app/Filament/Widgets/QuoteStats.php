<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;

class QuoteStats extends StatsOverviewWidget
{
    // First on the dashboard, above the chart (sort -4).
    protected static ?int $sort = -5;

    protected function getStats(): array
    {
        $quotes = Quote::query()->where('created_at', '>=', Carbon::today()->subDays(29));
        $total = (clone $quotes)->count();
        $accepted = (clone $quotes)->where('status', QuoteStatus::Accepted);
        $acceptedCount = (clone $accepted)->count();
        $currency = (string) config('quotecalc.currency');

        return [
            Stat::make('Quotes, last 30 days', $total),
            Stat::make('Accepted value', Number::currency((int) (clone $accepted)->sum('total_cents') / 100, $currency)),
            Stat::make('Acceptance rate', $total === 0 ? '—' : Number::percentage($acceptedCount / $total * 100)),
        ];
    }
}
