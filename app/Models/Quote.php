<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuoteStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference
 * @property QuoteStatus $status
 * @property string $customer_name
 * @property string $customer_email
 * @property string|null $customer_company
 * @property string|null $notes
 * @property string $currency
 * @property bool $include_vat
 * @property int $vat_rate_bps
 * @property int $net_cents
 * @property int $vat_cents
 * @property int $total_cents
 * @property string $source
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Quote extends Model
{
    protected $fillable = [
        'reference', 'status', 'customer_name', 'customer_email', 'customer_company', 'notes',
        'currency', 'include_vat', 'vat_rate_bps', 'net_cents', 'vat_cents', 'total_cents', 'source',
    ];

    protected $attributes = [
        'status' => 'new',
        'source' => 'web',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'include_vat' => 'boolean',
            'vat_rate_bps' => 'integer',
            'net_cents' => 'integer',
            'vat_cents' => 'integer',
            'total_cents' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    /** @return HasMany<QuoteItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    public function validUntil(): Carbon
    {
        return $this->created_at->copy()->addDays((int) config('quotecalc.valid_days'));
    }
}
