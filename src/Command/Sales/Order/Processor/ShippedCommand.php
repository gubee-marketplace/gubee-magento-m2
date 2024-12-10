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

        if($shippedStatus){
            $order->setState($shippedStatus);
            $order->setStatus($shippedStatus);
            $order->save();
        }
    }

    /**
     * Retrieves the shipped status for an order.
     *
     * This method fetches the shipped order status from the order status collection
     * and returns the corresponding Gubee status if it is linked to Gubee.
     *
     * @return string|null The shipped status if linked to Gubee, otherwise null.
     */
    private function getShippedStatus(): ?string
    {
        $shippedOrderStatus = $this->orderStatusCollectionFactory->create()
            ->joinStates()
            ->addFieldToFilter('main_table.gubee_status', 'SHIPPED')
            ->getFirstItem();

        if ($shippedOrderStatus && $shippedOrderStatus->getLinkedToGubee()) {
            return $shippedOrderStatus->getStatus();
        }

        return null;
    }
}
