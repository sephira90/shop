<?php

declare(strict_types=1);

namespace App\Support\Operations;

use App\Support\Operations\Dto\ConsoleCommandResultDto;
use Illuminate\Support\Facades\Artisan;

final class ConsoleCommandRunner
{
    /**
     * Run one Artisan command and capture its output.
     *
     * @param  array<string,bool|float|int|string>  $parameters
     */
    public function run(string $command, array $parameters = []): ConsoleCommandResultDto
    {
        $exitCode = Artisan::call($command, $parameters);

        return new ConsoleCommandResultDto(
            command: $command,
            exitCode: $exitCode,
            output: trim(Artisan::output()),
        );
    }
}
