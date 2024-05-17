<?php

declare(strict_types=1);

namespace Gubee\Integration\Api\Data;

use Gubee\Integration\Api\Data\OrderInterface;
use Magento\Framework\Api\SearchResultsInterface;

interface OrderSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get Order list.
     *
     * @return OrderInterface[]
     */
    public function getItems();

    /**
     * Set order_id list.
     *
     * @param OrderInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
