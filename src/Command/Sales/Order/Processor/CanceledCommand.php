<?php

declare(strict_types=1);

namespace Gubee\Integration\Command\Sales\Order\Processor;

use Exception;
use Gubee\Integration\Api\OrderRepositoryInterface as GubeeOrderRepositoryInterface;
use Gubee\Integration\Command\Sales\Order\AbstractProcessorCommand;
use Gubee\SDK\Resource\Sales\OrderResource;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\InvoiceManagementInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Creditmemo\ItemCreationFactory;
use Magento\Sales\Model\Order\Status\HistoryFactory;
use Magento\Sales\Model\RefundOrder;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Exception\LogicException;
use Throwable;

use function __;
use function sprintf;

class CanceledCommand extends AbstractProcessorCommand
{
    protected RefundOrder $refundOrder;
    protected ItemCreationFactory $itemCreationFactory;
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
        InvoiceManagementInterface $invoiceManagement,
        RefundOrder $refundOrder,
        ItemCreationFactory $itemCreationFactory,
        ?string $name = null
    ) {
        $this->refundOrder         = $refundOrder;
        $this->invoiceManagement   = $invoiceManagement;
        $this->itemCreationFactory = $itemCreationFactory;
        parent::__construct(
            $eventDispatcher,
            $logger,
            $orderResource,
            $orderCollectionFactory,
            $orderRepository,
            $gubeeOrderRepository,
            $historyFactory,
            $orderManagement,
            "canceled"
        );
    }

    protected function doExecute(): int
    {
        $order = $this->getOrder(
            $this->input->getArgument('order_id')
        );
        /** check if order is aready cancelled */
        if ($order->getState() === 'canceled') {
            $this->logger->info(
                sprintf(
                    "Order with ID %s is already canceled",
                    $order->getId()
                )
            );
            return 0;
        }

        $this->cancelOrder($order);

        return 0;
    }

    private function cancelOrder($order): void
    {
        try {
            if ($order->canCreditmemo() && $order->hasInvoice()) {
                $itemIdsToRefund = [];
                foreach ($order->getAllItems() as $orderItem) {
                    $creditMemoItem = $this->itemCreationFactory->create();
                    $creditMemoItem->setQty($orderItem->getQtyOrdered())
                                    ->setOrderItemId($orderItem->getId());
                    $itemIdsToRefund[] = $creditMemoItem;
                }

                try {
                    $this->refundOrder->execute($order->getId(), $itemIdsToRefund);
                    $this->addOrderHistory(
                        __('Order refunded!')->__toString(),
                        (int) $order->getId()
                    );
                } catch (Exception $e) {
                    $this->logger->error(
                        $e->getMessage()
                    );
                }
            }

            $order->cancel();
            
            $order->save();
            $this->addOrderHistory(
                __('Order canceled!')->__toString(),
                (int) $order->getId()
            );
            $this->logger->info(
                sprintf(
                    "Order with ID %s canceled",
                    (string) $order->getId()
                )
            );
        } catch (Throwable $e) {
            $this->logger->error(
                sprintf(
                    "Error canceling order with ID %s: %s",
                    $order->getId(),
                    $e->getMessage()
                )
            );
        }
    }

    public function getPriority(): int
    {
        return 950;
    }
}
