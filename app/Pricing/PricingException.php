<?php

declare(strict_types=1);

namespace App\Pricing;

use DomainException;

/**
 * Thrown when a configuration cannot be priced (e.g. size outside product limits).
 * Carries the input field it relates to so HTTP layers can map it to a validation error.
 */
final class PricingException extends DomainException
{
    public function __construct(public readonly string $field, string $message)
    {
        parent::__construct($message);
    }
}
