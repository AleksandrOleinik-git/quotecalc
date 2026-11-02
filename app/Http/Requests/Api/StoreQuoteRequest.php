<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Actions\Data\CustomerData;
use App\Actions\Data\ItemSelection;
use App\Http\Requests\Concerns\HasItemRules;
use Illuminate\Foundation\Http\FormRequest;

/**
 * API quote: a customer and one or more calculator lines.
 */
class StoreQuoteRequest extends FormRequest
{
    use HasItemRules;

    private const MAX_ITEMS = 20;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'customer' => ['required', 'array'],
            'customer.name' => ['required', 'string', 'max:120'],
            'customer.email' => ['required', 'email', 'max:190'],
            'customer.company' => ['nullable', 'string', 'max:190'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'include_vat' => ['sometimes', 'boolean'],
            'items' => ['required', 'array', 'min:1', 'max:'.self::MAX_ITEMS],
            ...self::itemRules('items.*.'),
        ];
    }

    public function customer(): CustomerData
    {
        $company = $this->input('customer.company');
        $notes = $this->input('notes');

        return new CustomerData(
            name: trim((string) $this->input('customer.name')),
            email: trim((string) $this->input('customer.email')),
            company: is_string($company) && $company !== '' ? trim($company) : null,
            notes: is_string($notes) && $notes !== '' ? trim($notes) : null,
        );
    }

    /**
     * @return list<ItemSelection>
     */
    public function selections(): array
    {
        /** @var list<array{product_id: int|string, material_id: int|string, width_mm: int|string, height_mm: int|string, quantity: int|string, option_ids?: array<int|string>|null}> $items */
        $items = array_values($this->validated('items'));

        return array_map(ItemSelection::fromArray(...), $items);
    }

    public function includeVat(): bool
    {
        return $this->boolean('include_vat');
    }
}
