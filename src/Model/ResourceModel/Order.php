<?php

declare(strict_types=1);

namespace Gubee\Integration\Model\ResourceModel;

use Gubee\Integration\Api\Data\OrderInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Order extends AbstractDb
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('gubee_integration_order', OrderInterface::ENTITY_ID);
    }
}
