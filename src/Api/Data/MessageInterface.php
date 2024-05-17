<?php

declare(strict_types=1);

namespace Gubee\Integration\Api\Data;

use DateTimeInterface;
use Gubee\Integration\Api\Enum\Message\StatusEnum;

interface MessageInterface
{
    public const TABLE      = 'gubee_integration_queue_message';
    public const MESSAGE_ID = 'message_id';
    public const COMMAND    = 'command';
    public const STATUS     = 'status';
    public const PAYLOAD    = 'payload';
    public const ATTEMPTS   = 'attempts';
    public const PRODUCT_ID = 'product_id';
    public const MESSAGE    = 'message';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * Get the message ID.
     *
     * @return int The message ID.
     */
    public function getMessageId(): int;

    /**
     * Set the message ID.
     *
     * @param int $messageId The message ID.
     */
    public function setMessageId(int $messageId): self;

    /**
     * Get the command.
     *
     * @return string The command.
     */
    public function getCommand(): string;

    /**
     * Set the command.
     *
     * @param string $command The command.
     */
    public function setCommand(string $command): self;

    /**
     * Get the status.
     *
     * @return string The status.
     */
    public function getStatus(): string;

    /**
     * Set the status.
     *
     * @param StatusEnum $status The status.
     */
    public function setStatus(StatusEnum $status): self;

    /**
     * Get the payload.
     *
     * @return array<int|string, mixed> The payload.
     */
    public function getPayload(): array;

    /**
     * Set the payload.
     *
     * @param array<int|string, mixed> $payload The payload.
     */
    public function setPayload(array $payload): self;

    /**
     * Get the number of attempts.
     *
     * @return int The number of attempts.
     */
    public function getAttempts(): int;

    /**
     * Set the product ID.
     *
     * @param int $productId The product ID.
     */
    public function setProductId(int $productId): self;

    /**
     * Get the product ID.
     *
     * @return int The product ID.
     */
    public function getProductId(): ?int;

    /**
     * Set the number of attempts.
     *
     * @param int $attempts The number of attempts.
     */
    public function setAttempts(int $attempts): self;

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
     * Get the creation date and time.
     *
     * @return DateTimeInterface The creation date and time.
     */
    public function getCreatedAt(): DateTimeInterface;

    /**
     * Set the creation date and time.
     *
     * @param string $createdAt The creation date and time.
     */
    public function setCreatedAt(string $createdAt): self;

    /**
     * Get the last update date and time.
     *
     * @return DateTimeInterface The last update date and time.
     */
    public function getUpdatedAt(): DateTimeInterface;

    /**
     * Set the last update date and time.
     *
     * @param string $updatedAt The last update date and time.
     */
    public function setUpdatedAt(string $updatedAt): self;
}
