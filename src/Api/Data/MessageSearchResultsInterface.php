<?php

declare(strict_types=1);

namespace Gubee\Integration\Api\Data;

use Gubee\Integration\Api\Data\MessageInterface;
use Magento\Framework\Api\SearchResultsInterface;

interface MessageSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get Message list.
     *
     * @return array<MessageInterface>
     */
    public function getItems(): array;

    /**
     * Set job list.
     *
     * @param array<MessageInterface> $items
     */
    public function setItems(array $items): self;
}
