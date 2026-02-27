<?php

declare(strict_types=1);

namespace App\Application\Checkout\Dto;

final readonly class CheckoutAddressInputDto
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            line1: trim((string) $validated['line1']),
            city: trim((string) $validated['city']),
            country: strtoupper(trim((string) $validated['country'])),
            postcode: trim((string) $validated['postcode']),
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
