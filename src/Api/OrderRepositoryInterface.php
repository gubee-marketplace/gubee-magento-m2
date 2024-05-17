<?php

declare (strict_types = 1);

namespace Gubee\Integration\Api;

use Gubee\Integration\Api\Data\OrderInterface;
use Gubee\Integration\Api\Data\OrderSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface OrderRepositoryInterface {
    /**
     * Save Order
     *
     * @return OrderInterface
     * @throws LocalizedException
     */
    public function save(
        OrderInterface $order
    );

    /**
     * Retrieve Order
     *
     * @param string $orderId
     * @return OrderInterface
     * @throws LocalizedException
     */
    public function get($orderId);

    /**
     * Retrieve Order matching the specified criteria.
     *
     * @param string $gubeeOrderId
     * @return OrderInterface
     */
    public function getByGubeeOrderId($gubeeOrderId);

    /**
     * Retrieve Order matching the specified criteria.
     *
     * @param string $orderId
     * @return OrderInterface
     */
    public function getByOrderId($orderId);

    /**
     * Retrieve Order matching the specified criteria.
     *
     * @return OrderSearchResultsInterface
     * @throws LocalizedException
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete Order
     *
     * @return bool true on success
     * @throws LocalizedException
     */
    public function delete(
        OrderInterface $order
    );

    /**
     * Delete Order by ID
     *
     * @param string $orderId
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById($orderId);
}
