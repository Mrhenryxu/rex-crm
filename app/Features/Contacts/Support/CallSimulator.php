<?php

namespace App\Features\Contacts\Support;

use App\Features\Contacts\Enums\CallOutcome;

/**
 * Mock dialler. No call is placed; an outcome is picked at random.
 * Not final so tests can substitute a deterministic instance through the container.
 */
class CallSimulator
{
    public function simulate(string $phone): CallOutcome
    {
        $outcomes = CallOutcome::cases();

        return $outcomes[random_int(0, count($outcomes) - 1)];
    }
}
