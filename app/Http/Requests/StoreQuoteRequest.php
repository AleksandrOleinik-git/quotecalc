<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Actions\Data\CustomerData;

/**
 * Web "Request quote" form: one calculator line plus contact details.
 */
class StoreQuoteRequest extends CalculatePriceRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'company' => ['nullable', 'string', 'max:190'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function customer(): CustomerData
    {
        return new CustomerData(
            name: $this->string('name')->trim()->toString(),
            email: $this->string('email')->trim()->toString(),
            company: $this->filled('company') ? $this->string('company')->trim()->toString() : null,
            notes: $this->filled('notes') ? $this->string('notes')->trim()->toString() : null,
        );
    }
}
