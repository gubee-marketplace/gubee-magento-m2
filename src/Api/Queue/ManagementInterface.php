<?php

declare(strict_types=1);

namespace Gubee\Integration\Api\Queue;

interface ManagementInterface
{
    /**
     * Append a job to the queue with the given command and parameters.
     *
     * @param array<int|string, mixed> $params
     */
    public function append(string $command, array $params = []): self;
}
