<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Filament\Widgets\QuotesPerDayChart;
use App\Filament\Widgets\QuoteStats;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class QuotesPerDayChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_chart_renders_for_admins(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(QuotesPerDayChart::class)->assertSee('Quotes per day, last 30 days');
    }

    public function test_stats_show_quote_count(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(QuoteStats::class)->assertSee('Acceptance rate');
    }
}
