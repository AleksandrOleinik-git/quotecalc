<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Quote;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class QuotesPerDayChart extends ChartWidget
{
    protected static ?string $heading = 'Quotes per day, last 30 days';

    // Right under the page title, above the account widget (sort -3).
    protected static ?int $sort = -4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '260px';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $days = 30;
        $from = Carbon::today()->subDays($days - 1);

        $counts = Quote::query()
            ->where('created_at', '>=', $from)
            ->pluck('created_at')
            ->countBy(fn (Carbon $createdAt): string => $createdAt->toDateString());

        $labels = [];
        $data = [];
        for ($day = $from->copy(); $day->lte(Carbon::today()); $day->addDay()) {
            $labels[] = $day->format('d M');
            $data[] = $counts->get($day->toDateString(), 0);
        }

        return [
            'datasets' => [['label' => 'Quotes', 'data' => $data]],
            'labels' => $labels,
        ];
    }
}
