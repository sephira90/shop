<?php

declare(strict_types=1);

namespace App\Application\Checkout\Dto;

use App\Support\Data\TypedValue;

final readonly class CheckoutAddressInputDto
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            line1: TypedValue::trimmedString($validated['line1']),
            city: TypedValue::trimmedString($validated['city']),
            country: strtoupper(TypedValue::trimmedString($validated['country'])),
            postcode: TypedValue::trimmedString($validated['postcode']),
        );
    }

    public function __construct(
        public string $line1,
        public string $city,
        public string $country,
        public string $postcode,
    ) {}

    /**
     * @return array{line1:string, city:string, country:string, postcode:string}
     */
    public function toArray(): array
    {
        return [
            'line1' => $this->line1,
            'city' => $this->city,
            'country' => $this->country,
            'postcode' => $this->postcode,
        ];
    }
}
