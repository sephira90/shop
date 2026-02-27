<?php

declare(strict_types=1);

namespace App\Support\Data;

final readonly class JsonPayload
{
    /**
     * @param  array<string, mixed>  $data
     */
    private function __construct(
        private array $data,
    ) {}

    /**
     * Create payload object from raw array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * Export payload as plain array for transport or persistence boundaries.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
