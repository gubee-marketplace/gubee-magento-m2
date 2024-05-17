<?php

declare(strict_types=1);

namespace Gubee\Integration\Api\Data\Message;

use Gubee\Integration\Api\Data\Message\DetailInterface;
use Magento\Framework\Api\SearchResultsInterface;

interface DetailSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get Detail list.
     *
     * @return DetailInterface[]
     */
    public function getItems();

    /**
     * Set level list.
     *
     * @param DetailInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
