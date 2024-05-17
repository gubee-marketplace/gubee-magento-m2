<?php

declare(strict_types=1);

namespace Gubee\Integration\Api\Data;

use Gubee\Integration\Api\Data\InvoiceInterface;
use Magento\Framework\Api\SearchResultsInterface;

interface InvoiceSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get Invoice list.
     *
     * @return InvoiceInterface[]
     */
    public function getItems();

    /**
     * Set danfeLink list.
     *
     * @param InvoiceInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
