<?php

declare(strict_types=1);

namespace Gubee\Integration\Command\Sales\Order\Processor;

use Gubee\Integration\Api\OrderRepositoryInterface as GubeeOrderRepositoryInterface;
use Gubee\Integration\Command\Sales\Order\AbstractProcessorCommand;
use Gubee\SDK\Resource\Sales\OrderResource;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Status\HistoryFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Exception\LogicException;
use \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory as OrderStatusCollectionFactory;

use function __;

class DeliveredCommand extends AbstractProcessorCommand
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
        GubeeOrderRepositoryInterface $gubeeOrderRepository,
        HistoryFactory $historyFactory,
        OrderManagementInterface $orderManagement,
        OrderStatusCollectionFactory $orderStatusCollectionFactory,
        ?string $name = null
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
            "delivered"
        );
    }

    protected function doExecute(): int
    {
        $order = $this->getOrder(
            $this->getInput()->getArgument('order_id')
        );

        if (in_array($order->getState(), [Order::STATE_CANCELED, Order::STATE_CLOSED, Order::STATE_HOLDED])) {
            $this->logger->info(
                __('Not delivering order %1 since it is canceled', $order->getIncrementId())
            );
            return 0;
        }

        $this->deliverOrder($order);

        return 0;
    }

    private function deliverOrder($order): void
    {
        // set order as completed
        $this->addOrderHistory(
            __("Order was delivered!")->__toString(),
            (int) $order->getId()
        );

        $deliveredStatus = $this->getDeliveredStatus();

        $order->setState($deliveredStatus);
        $order->setStatus($deliveredStatus);
        $order->save();
    }

    /**
     * Retrieve the Gubee delivered status.
     *
     * This method fetches the delivered order status from the order status collection,
     * checks if it is linked to Gubee, and returns the corresponding Gubee status.
     * If no linked status is found, it defaults to the complete order state.
     *
     * @return string The Gubee delivered status or the default complete order state.
     */
    private function getDeliveredStatus(): string
    {
        $deliveredOrderStatus = $this->orderStatusCollectionFactory->create()
            ->joinStates()
            ->addFieldToFilter('main_table.status', 'delivered')
            ->getFirstItem();

        if ($deliveredOrderStatus && $deliveredOrderStatus->getLinkedToGubee()) {
            return strtolower(
                $deliveredOrderStatus->getGubeeStatus()
            );
        }

        return Order::STATE_COMPLETE;
    }
}
