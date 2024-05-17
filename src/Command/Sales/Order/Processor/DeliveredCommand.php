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

use function __;

class DeliveredCommand extends AbstractProcessorCommand
{
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
        ?string $name = null
    ) {
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
        $order->setState('complete');
        $order->setStatus('complete');
        $order->save();
    }
}
