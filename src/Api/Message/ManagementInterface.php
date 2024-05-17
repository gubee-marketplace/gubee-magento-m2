<?php

declare(strict_types=1);

namespace Gubee\Integration\Api\Message;

use Gubee\Integration\Api\Data\MessageInterface;

interface ManagementInterface
{
    /**
     * Process given queue message
     */
    public function process(MessageInterface $message): void;

    /**
     * Process a bunch of messages at once
     *
     * @param iterable<MessageInterface> $messages
     */
    public function massProcess(iterable $messages): void;

    /**
     * Get messages waiting to be processed
     */
    public function getPending();

    /**
     * Get failed messages waiting to be retried
     *
     * @return array<MessageInterface>
     */
    public function getToBeRetried(): array;
}
