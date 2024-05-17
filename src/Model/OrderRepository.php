<?php

declare(strict_types=1);

namespace Gubee\Integration\Model;

use Exception;
use Gubee\Integration\Api\Data\OrderInterface;
use Gubee\Integration\Api\Data\OrderInterfaceFactory;
use Gubee\Integration\Api\Data\OrderSearchResultsInterfaceFactory;
use Gubee\Integration\Api\OrderRepositoryInterface;
use Gubee\Integration\Model\ResourceModel\Order as ResourceOrder;
use Gubee\Integration\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

use function __;

class OrderRepository implements OrderRepositoryInterface
{
    /** @var OrderInterfaceFactory */
    protected $orderFactory;

    /** @var OrderCollectionFactory */
    protected $orderCollectionFactory;

    /** @var CollectionProcessorInterface */
    protected $collectionProcessor;

    /** @var Order */
    protected $searchResultsFactory;

    /** @var ResourceOrder */
    protected $resource;

    public function __construct(
        ResourceOrder $resource,
        OrderInterfaceFactory $orderFactory,
        OrderCollectionFactory $orderCollectionFactory,
        OrderSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource               = $resource;
        $this->orderFactory           = $orderFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->searchResultsFactory   = $searchResultsFactory;
        $this->collectionProcessor    = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(OrderInterface $order)
    {
        try {
            $this->resource->save($order);
        } catch (Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the order: %1',
                $exception->getMessage()
            ));
        }
        return $order;
    }

    /**
     * @inheritDoc
     */
    public function get($orderId)
    {
        $order = $this->orderFactory->create();
        $this->resource->load($order, $orderId);
        if (! $order->getId()) {
            throw new NoSuchEntityException(__('Order with id "%1" does not exist.', $orderId));
        }
        return $order;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        SearchCriteriaInterface $criteria
    ) {
        $collection = $this->orderCollectionFactory->create();

        $this->collectionProcessor->process($criteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function delete(OrderInterface $order)
    {
        try {
            $orderModel = $this->orderFactory->create();
            $this->resource->load($orderModel, $order->getOrderId());
            $this->resource->delete($orderModel);
        } catch (Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Order: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($orderId)
    {
        return $this->delete($this->get($orderId));
    }

    /**
     * Retrieve Order matching the specified criteria.
     *
     * @param string $gubeeOrderId
     * @return OrderInterface
     */
    public function getByGubeeOrderId($gubeeOrderId)
    {
        $order = $this->orderFactory->create();
        $this->resource->load($order, $gubeeOrderId, 'gubee_order_id');
        if (! $order->getId()) {
            throw new NoSuchEntityException(__('Order with Gubee ID "%1" does not exist.', $gubeeOrderId));
        }
        return $order;
    }

    /**
     * Retrieve Order matching the specified criteria.
     *
     * @param string $orderId
     * @return OrderInterface
     */
    public function getByOrderId($orderId)
    {
        $order = $this->orderFactory->create();
        $this->resource->load($order, $orderId, 'order_id');
        if (! $order->getId()) {
            throw new NoSuchEntityException(__('Order with ID "%1" does not exist.', $orderId));
        }
        return $order;
    }
}
