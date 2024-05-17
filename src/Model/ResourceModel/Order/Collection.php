<?php

declare(strict_types=1);

namespace Gubee\Integration\Model\ResourceModel\Order;

use Gubee\Integration\Api\Data\OrderInterface;
use Gubee\Integration\Model\Order;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /** @inheritDoc */
    protected $_idFieldName = OrderInterface::ENTITY_ID;

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            Order::class,
            \Gubee\Integration\Model\ResourceModel\Order::class
        );
    }
}
