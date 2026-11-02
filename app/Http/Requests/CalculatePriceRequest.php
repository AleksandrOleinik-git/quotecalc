<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Actions\Data\ItemSelection;
use App\Http\Requests\Concerns\HasItemRules;
use Illuminate\Foundation\Http\FormRequest;

class CalculatePriceRequest extends FormRequest
{
    use HasItemRules;

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
            ...self::itemRules(),
            'include_vat' => ['sometimes', 'boolean'],
        ];
    }

    public function selection(): ItemSelection
    {
        /** @var array{product_id: int|string, material_id: int|string, width_mm: int|string, height_mm: int|string, quantity: int|string, option_ids?: array<int|string>|null} $data */
        $data = $this->validated();

        return ItemSelection::fromArray($data);
    }

    public function includeVat(): bool
    {
        return $this->boolean('include_vat');
    }
}
