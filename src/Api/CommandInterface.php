<?php

declare(strict_types=1);

namespace Gubee\Integration\Api;

interface CommandInterface
{
    /**
     * Get the command max attempts.
     */
    public function getMaxAttempts(): int;
}
