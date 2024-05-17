<?php

declare(strict_types=1);

namespace Gubee\Integration\Model;

use Gubee\Integration\Api\Data\OrderInterface;
use Magento\Framework\Model\AbstractModel;

class Order extends AbstractModel implements OrderInterface
{
    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Gubee\Integration\Model\ResourceModel\Order::class);
    }

    /**
     * @inheritDoc
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * @inheritDoc
     */
    public function getGubeeOrderId()
    {
        return $this->getData(self::GUBEE_ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setGubeeOrderId($gubeeOrderId)
    {
        return $this->setData(self::GUBEE_ORDER_ID, $gubeeOrderId);
    }

    /**
     * @inheritDoc
     */
    public function getGubeeMarketplace()
    {
        return $this->getData(self::GUBEE_MARKETPLACE);
    }

    /**
     * @inheritDoc
     */
    public function setGubeeMarketplace($gubeeMarketplace)
    {
        return $this->setData(self::GUBEE_MARKETPLACE, $gubeeMarketplace);
    }
}
