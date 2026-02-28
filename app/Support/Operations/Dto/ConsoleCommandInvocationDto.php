<?php

declare(strict_types=1);

namespace App\Support\Operations\Dto;

final readonly class ConsoleCommandInvocationDto
{
    /**
     * @param  array<string,bool|float|int|string>  $parameters
     */
    public function __construct(
        public string $command,
        public array $parameters,
    ) {}

    /**
     * @return array<string,string>
     */
    public function stringifyParameters(): array
    {
        $result = [];

        foreach ($this->parameters as $key => $value) {
            if (is_bool($value)) {
                $result[$key] = $value ? 'true' : 'false';

                continue;
            }

            $result[$key] = (string) $value;
        }

        return $result;
    }
}
