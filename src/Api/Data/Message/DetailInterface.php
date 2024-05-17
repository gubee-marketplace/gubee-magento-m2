<?php

declare(strict_types=1);

namespace Gubee\Integration\Api\Data\Message;

use DateTimeInterface;

interface DetailInterface
{
    public const TABLE      = 'gubee_integration_queue_message_detail';
    public const DETAIL_ID  = 'detail_id';
    public const LEVEL      = 'level';
    public const MESSAGE_ID = 'message_id';
    public const MESSAGE    = 'message';
    public const CONTEXT    = 'context';
    public const CREATED_AT = 'created_at';

    /**
     * Get the detail ID.
     *
     * @return int The detail ID.
     */
    public function getDetailId(): int;

    /**
     * Set the detail ID.
     *
     * @param int $detailId The detail ID.
     */
    public function setDetailId($detailId): self;

    /**
     * Get the level.
     *
     * @return int The level.
     */
    public function getLevel(): int;

    /**
     * Set the level.
     *
     * @param int $level The level.
     */
    public function setLevel(int $level): self;

    /**
     * Get the message ID.
     *
     * @return int|null The message ID.
     */
    public function getMessageId(): ?int;

    /**
     * Set the message ID.
     *
     * @param int $messageId The message ID.
     */
    public function setMessageId(int $messageId): self;

    /**
     * Get the message.
     *
     * @return string The message.
     */
    public function getMessage(): string;

    /**
     * Set the message.
     *
     * @param string $message The message.
     */
    public function setMessage(string $message): self;

    /**
     * Get the context.
     *
     * @return array The context.
     */
    public function getContext(): array;

    /**
     * Set the context.
     *
     * @param array $context The context.
     */
    public function setContext(array $context): self;

    /**
     * Get the creation date and time.
     *
     * @return DateTimeInterface The creation date and time.
     */
    public function getCreatedAt(): DateTimeInterface;

    /**
     * Set the creation date and time.
     *
     * @param DateTimeInterface $createdAt The creation date and time.
     */
    public function setCreatedAt(DateTimeInterface $createdAt): self;
}
