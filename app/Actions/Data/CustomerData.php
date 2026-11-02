<?php

declare(strict_types=1);

namespace App\Actions\Data;

final readonly class CustomerData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $company = null,
        public ?string $notes = null,
    ) {}
}
