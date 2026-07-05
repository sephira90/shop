<?php

declare(strict_types=1);

namespace App\Domains\Users\Application\Dto;

final readonly class AccountOrderAddressResultDto
{
    public static function fromPayload(mixed $value): ?self
    {
        if (! is_array($value)) {
            return null;
        }

        return new self(
            line1: self::nullableString($value['line1'] ?? null),
            city: self::nullableString($value['city'] ?? null),
            country: self::nullableString($value['country'] ?? null),
            postcode: self::nullableString($value['postcode'] ?? null),
        );
    }

    public function __construct(
        public ?string $line1,
        public ?string $city,
        public ?string $country,
        public ?string $postcode,
    ) {}

    /**
     * @return array{line1:string|null,city:string|null,country:string|null,postcode:string|null}
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

    private static function nullableString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? $value : null;
    }
}
