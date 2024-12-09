<?php

declare(strict_types=1);

namespace Gubee\Integration\Command\Sales\Order\Processor;

use Gubee\Integration\Api\OrderRepositoryInterface as GubeeOrderRepositoryInterface;
use Gubee\Integration\Command\Sales\Order\AbstractProcessorCommand;
use Gubee\SDK\Resource\Sales\OrderResource;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Status\HistoryFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Exception\LogicException;
use \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory as OrderStatusCollectionFactory;
use Magento\Sales\Model\Order;

class ShippedCommand extends AbstractProcessorCommand
{
    private OrderStatusCollectionFactory $orderStatusCollectionFactory;

    /**
     * @param string|null $name The name of the command; passing null means it must be set in configure()
     * @throws LogicException When the command name is empty.
     */
    public function __construct(
        ManagerInterface $eventDispatcher,
        LoggerInterface $logger,
        OrderResource $orderResource,
        CollectionFactory $orderCollectionFactory,
        OrderRepositoryInterface $orderRepository,
        HistoryFactory $historyFactory,
        GubeeOrderRepositoryInterface $gubeeOrderRepository,
        OrderManagementInterface $orderManagement,
        OrderStatusCollectionFactory $orderStatusCollectionFactory
    ) {
        $this->orderStatusCollectionFactory = $orderStatusCollectionFactory;

        parent::__construct(
            $eventDispatcher,
            $logger,
            $orderResource,
            $orderCollectionFactory,
            $orderRepository,
            $gubeeOrderRepository,
            $historyFactory,
            $orderManagement,
            "shipped"
        );
    }

    protected function doExecute(): int
    {
        $order = $this->getOrder(
            $this->getInput()->getArgument('order_id')
        );
        $this->shippedOrder($order);

        return 0;
    }

    private function shippedOrder($order): void
    {
        $this->addOrderHistory(
            __("Order was shipped!")->__toString(),
            (int) $order->getId()
        );

        $shippedStatus = $this->getShippedStatus();

        $order->setState($shippedStatus);
        $order->setStatus($shippedStatus);
        $order->save();
    }

    /**
     * Retrieves the shipped status for an order.
     *
     * This method fetches the shipped order status from the order status collection,
     * checks if it is linked to Gubee, and returns the corresponding Gubee status
     * in lowercase. If the shipped order status is not linked to Gubee, it returns
     * the default order state as complete.
     *
     * @return string The shipped status in lowercase if linked to Gubee, otherwise the default order state.
     */
    private function getShippedStatus(): string
    {
        $shippedOrderStatus = $this->orderStatusCollectionFactory->create()
            ->joinStates()
            ->addFieldToFilter('main_table.status', 'ip_shipped')
            ->getFirstItem();

        if ($shippedOrderStatus && $shippedOrderStatus->getLinkedToGubee()) {
            return strtolower(
                $shippedOrderStatus->getGubeeStatus()
            );
        }

        return 'ip_shipped';
    }
}
