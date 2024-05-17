<?php

declare(strict_types=1);

namespace Gubee\Integration\Command\Sales\Order\Processor;

use Exception;
use Gubee\Integration\Api\OrderRepositoryInterface as GubeeOrderRepositoryInterface;
use Gubee\Integration\Command\Sales\Order\AbstractProcessorCommand;
use Gubee\SDK\Resource\Sales\OrderResource;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Status\HistoryFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Psr\Log\LoggerInterface;

use function __;

class PaidCommand extends AbstractProcessorCommand
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
            "payed"
        );
    }

    protected function doExecute(): int
    {
        $gubeeOrder = $this->orderResource->loadByOrderId(
            $this->getInput()->getArgument('order_id')
        );
        $order      = $this->getOrder($this->getInput()->getArgument('order_id'));
        try {
            if (in_array($order->getState(), [Order::STATE_CANCELED, Order::STATE_CLOSED, Order::STATE_HOLDED])) {
                $this->logger->info(
                    __('Not paying order %1 since its state is: \`%2\`', $order->getIncrementId(), $order->getState())
                );
                return 0;
            }
    
            $order->setTotalPaid(
                $order->getGrandTotal()
            );
            $order->setState(
                'processing'
            );
            $order->setStatus(
                'processing'
            );
            $this->orderRepository->save($order);
            $this->addOrderHistory(
                __(
                    "Order paid"
                )->__toString(),
                (int) $order->getId()
            );
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return 1;
        }

        return 0;
    }
}
