<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function adminPages(): array
    {
        return [
            'dashboard' => ['/admin'],
            'products' => ['/admin/products'],
            'new product' => ['/admin/products/create'],
            'quotes' => ['/admin/quotes'],
        ];
    }

    #[DataProvider('adminPages')]
    public function test_guests_are_redirected_to_login(string $url): void
    {
        $this->get($url)->assertRedirect('/admin/login');
    }

    #[DataProvider('adminPages')]
    public function test_non_admin_users_are_forbidden(string $url): void
    {
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
    }

    #[DataProvider('adminPages')]
    public function test_admins_can_open_pages(string $url): void
    {
        $this->actingAs(User::factory()->admin()->create())->get($url)->assertOk();
    }

    public function test_admin_sees_quote_breakdown(): void
    {
        $this->seed();
        $quote = Quote::query()->latest('id')->firstOrFail();

        $this->actingAs(User::factory()->admin()->create())
            ->get("/admin/quotes/{$quote->reference}")
            ->assertOk()
            ->assertSee($quote->reference)
            ->assertSee($quote->items()->firstOrFail()->product_name);
    }

    public function test_admin_can_edit_product_with_relations(): void
    {
        $this->seed();
        $product = Product::query()->firstOrFail();

        $this->actingAs(User::factory()->admin()->create())
            ->get("/admin/products/{$product->id}/edit")
            ->assertOk()
            ->assertSee('Materials')
            ->assertSee('Quantity tiers');
    }
}
