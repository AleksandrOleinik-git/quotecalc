<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_demo_catalogue_admin_and_quotes(): void
    {
        $this->seed();

        $this->assertSame(
            [4, true, 3],
            [Product::count(), User::where('email', AdminUserSeeder::EMAIL)->sole()->is_admin, Quote::count()],
        );
    }
}
