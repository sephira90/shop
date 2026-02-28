<?php

declare(strict_types=1);

namespace App\Support\Smoke;

use InvalidArgumentException;

final class SmokeScenarioSelector
{
    /**
     * @template TScenario of object
     *
     * @param  array<string,TScenario>  $orderedScenarios
     * @param  list<string>  $only
     * @return list<TScenario>
     */
    public function select(array $orderedScenarios, array $only, string $label): array
    {
        if ($only === []) {
            return array_values($orderedScenarios);
        }

        $selected = [];

        foreach ($only as $name) {
            if (! array_key_exists($name, $orderedScenarios)) {
                throw new InvalidArgumentException(sprintf(
                    'Option --only contains unknown %s scenario "%s".',
                    $label,
                    $name,
                ));
            }

            $selected[] = $orderedScenarios[$name];
        }

        return $selected;
    }

    /**
     * @template TScenario of object
     *
     * @param  array<string,TScenario>  $orderedScenarios
     * @return list<string>
     */
    public function names(array $orderedScenarios): array
    {
        return array_keys($orderedScenarios);
    }
}
