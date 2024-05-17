<?php

declare(strict_types=1);

namespace Gubee\Integration\Model\Message;

use DateTimeInterface;
use Gubee\Integration\Api\Data\Message\DetailInterface;
use Magento\Framework\Model\AbstractModel;

use function is_string;
use function json_decode;
use function json_encode;
use function json_last_error;

use const JSON_ERROR_NONE;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class Detail extends AbstractModel implements DetailInterface
{
    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Gubee\Integration\Model\ResourceModel\Message\Detail::class);
    }

    public function beforeSave(): self
    {
        if ($this->getData(self::CONTEXT) !== null) {
            $context = $this->getData(self::CONTEXT);
            if (is_string($context)) {
                if (! $this->isJson($context)) {
                    $context = json_decode($context, true);
                }
            } else {
                $context = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            parent::setData(self::CONTEXT, $context);
        }
        return parent::beforeSave();
    }

    /**
     * Get the detail ID.
     *
     * @return int The detail ID.
     */
    public function getDetailId(): int
    {
        return $this->getData(self::DETAIL_ID);
    }

    /**
     * Set the detail ID.
     *
     * @param int $detailId The detail ID.
     */
    public function setDetailId($detailId): self
    {
        return $this->setData(self::DETAIL_ID, $detailId);
    }

    /**
     * Get the level.
     *
     * @return int The level.
     */
    public function getLevel(): int
    {
        return $this->getData(self::LEVEL);
    }

    /**
     * Set the level.
     *
     * @param int $level The level.
     */
    public function setLevel(int $level): self
    {
        return $this->setData(self::LEVEL, $level);
    }

    /**
     * Get the message ID.
     *
     * @return int|null The message ID.
     */
    public function getMessageId(): ?int
    {
        return $this->getData(self::MESSAGE_ID);
    }

    /**
     * Set the message ID.
     *
     * @param int $messageId The message ID.
     */
    public function setMessageId(int $messageId): self
    {
        return $this->setData(self::MESSAGE_ID, $messageId);
    }

    /**
     * Get the message.
     *
     * @return string The message.
     */
    public function getMessage(): string
    {
        return $this->getData(self::MESSAGE);
    }

    /**
     * Set the message.
     *
     * @param string $message The message.
     */
    public function setMessage(string $message): self
    {
        return $this->setData(self::MESSAGE, $message);
    }

    /**
     * Get the context.
     *
     * @return array<int|string, mixed> The context.
     */
    public function getContext(): array
    {
        $context = $this->getData(self::CONTEXT);
        if (is_string($context)) {
            return $this->isJson($context) ? json_decode($context, true) : $context;
        }
        return $this->getData(self::CONTEXT);
    }

    /**
     * Set the context.
     *
     * @param array<int|string, mixed> $context The context.
     */
    public function setContext(array $context): self
    {
        return $this->setData(self::CONTEXT, $context);
    }

    /**
     * Get the creation date and time.
     *
     * @return DateTimeInterface The creation date and time.
     */
    public function getCreatedAt(): DateTimeInterface
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Set the creation date and time.
     *
     * @param DateTimeInterface $createdAt The creation date and time.
     */
    public function setCreatedAt(DateTimeInterface $createdAt): self
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    protected function isJson(string $content): bool
    {
        json_decode($content);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
