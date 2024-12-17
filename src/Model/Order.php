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

    /**
     * Get gubee_channel
     *
     * @return string|null
     */
    public function getGubeeChannel(){
        return $this->getData(self::GUBEE_CHANNEL);
    }

    /**
     * Set gubee_channel
     *
     * @param string $gubeeChannel
     * @return \Gubee\Integration\Order\Api\Data\OrderInterface
     */
    public function setGubeeChannel($gubeeChannel){
        return $this->setData(self::GUBEE_CHANNEL, $gubeeChannel);
    }

     /**
     * Get gubee_account_id
     *
     * @return string|null
     */
    public function getGubeeAccountId(){
        return $this->getData(self::GUBEE_ACCOUNT_ID);
    }

    /**
     * Set gubee_account_id
     *
     * @param string $accountId
     * @return \Gubee\Integration\Order\Api\Data\OrderInterface
     */
    public function setGubeeAccountId($accountId){
        return $this->setData(self::GUBEE_ACCOUNT_ID, $accountId);
    }

    /**
     * Get gubee_is_fulfillment
     *
     * @return bool|null
     */
    public function getFulfillment(){
        return $this->getData(self::GUBEE_FULFILLMENT);
    }

    /**
     * Set gubee_is_fulfillment
     *
     * @param bool $isFullfillment
     * @return \Gubee\Integration\Order\Api\Data\OrderInterface
     */
    public function setFulfillment($isFullfillment){
        return $this->setData(self::GUBEE_FULFILLMENT, $isFullfillment);
    }
}
