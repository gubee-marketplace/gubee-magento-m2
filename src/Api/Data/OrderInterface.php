<?php

declare(strict_types=1);

namespace Gubee\Integration\Api\Data;

interface OrderInterface
{
    public const ENTITY_ID         = 'entity_id';
    public const GUBEE_MARKETPLACE = 'gubee_marketplace';
    public const ORDER_ID          = 'order_id';
    public const GUBEE_ORDER_ID    = 'gubee_order_id';
    public const GUBEE_CHANNEL    = 'gubee_channel';
    public const GUBEE_ACCOUNT_ID    = 'gubee_account_id';
    public const GUBEE_FULFILLMENT    = 'gubee_fulfillment';

    /**
     * Get order_id
     *
     * @return string|null
     */
    public function getOrderId();

    /**
     * Set order_id
     *
     * @param string $orderId
     * @return \Gubee\Integration\Order\Api\Data\OrderInterface
     */
    public function setOrderId($orderId);

    /**
     * Get gubee_order_id
     *
     * @return string|null
     */
    public function getGubeeOrderId();

    /**
     * Set gubee_order_id
     *
     * @param string $gubeeOrderId
     * @return \Gubee\Integration\Order\Api\Data\OrderInterface
     */
    public function setGubeeOrderId($gubeeOrderId);

    /**
     * Get gubee_marketplace
     *
     * @return string|null
     */
    public function getGubeeMarketplace();

    /**
     * Set gubee_marketplace
     *
     * @param string $gubeeMarketplace
     * @return \Gubee\Integration\Order\Api\Data\OrderInterface
     */
    public function setGubeeMarketplace($gubeeMarketplace);

    /**
     * Get gubee_channel
     *
     * @return string|null
     */
    public function getGubeeChannel();

    /**
     * Set gubee_channel
     *
     * @param string $gubeeChannel
     * @return \Gubee\Integration\Order\Api\Data\OrderInterface
     */
    public function setGubeeChannel($gubeeChannel);

     /**
     * Get gubee_account_id
     *
     * @return string|null
     */
    public function getGubeeAccountId();

    /**
     * Set gubee_account_id
     *
     * @param string $accountId
     * @return \Gubee\Integration\Order\Api\Data\OrderInterface
     */
    public function setGubeeAccountId($accountId);

    /**
     * Get gubee_is_fulfillment
     *
     * @return bool|null
     */
    public function getFulfillment();

    /**
     * Set gubee_is_fulfillment
     *
     * @param bool $isFullfillment
     * @return \Gubee\Integration\Order\Api\Data\OrderInterface
     */
    public function setFulfillment($isFullfillment);
}
