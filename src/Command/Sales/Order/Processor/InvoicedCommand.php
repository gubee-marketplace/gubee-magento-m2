<?php

declare(strict_types=1);

namespace Gubee\Integration\Command\Sales\Order\Processor;

use DateTime;
use Gubee\Integration\Api\InvoiceRepositoryInterface;
use Gubee\Integration\Api\OrderRepositoryInterface as GubeeOrderRepositoryInterface;
use Gubee\Integration\Command\Sales\Order\AbstractProcessorCommand;
use Gubee\Integration\Model\Invoice;
use Gubee\Integration\Model\InvoiceFactory;
use Gubee\SDK\Resource\Sales\OrderResource;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Status\HistoryFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Model\Service\InvoiceService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Exception\LogicException;
use Throwable;

use function __;
use function count;

class InvoicedCommand extends AbstractProcessorCommand
{
    /** @var InvoiceService */
    protected $invoiceService;

    /** @var TransactionFactory */
    protected $transactionFactory;

    protected InvoiceFactory $invoiceFactory;
    protected InvoiceRepositoryInterface $invoiceRepository;

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
        InvoiceFactory $invoiceFactory,
        InvoiceRepositoryInterface $invoiceRepository,
        InvoiceService $invoiceService,
        OrderManagementInterface $orderManagement,
        TransactionFactory $transactionFactory
    ) {
        $this->invoiceService     = $invoiceService;
        $this->transactionFactory = $transactionFactory;
        $this->invoiceFactory     = $invoiceFactory;
        $this->invoiceRepository  = $invoiceRepository;
        parent::__construct(
            $eventDispatcher,
            $logger,
            $orderResource,
            $orderCollectionFactory,
            $orderRepository,
            $gubeeOrderRepository,
            $historyFactory,
            $orderManagement,
            "invoiced",
        );
    }

    protected function doExecute(): int
    {
        $order = $this->getOrder(
            $this->input->getArgument('order_id')
        );

        $this->logger->debug(
            __("Invoicing order '%1'", $order->getIncrementId())
        );

        if (in_array($order->getState(), [Order::STATE_CANCELED, Order::STATE_CLOSED, Order::STATE_HOLDED])) {
            $this->logger->info(
                __('Not invoicing order %1 since its state is: \`%2\`', $order->getIncrementId(), $order->getState())
            );
            return 0;
        }

        $this->invoiceOrder($order);
        $gubeeOrder = $this->gubeeOrderRepository->getByOrderId(
            $order->getId()
        );
        $this->logger->debug(
            __("A total of '%1' invoices were found", count($gubeeOrder['invoices'] ?? []))
        );
        $totalIntegrated = 0;
        foreach ($gubeeOrder['invoices'] ?? [] as $invoiceData) {
            $this->logger->debug(
                __("Integrating invoice '%1'", $invoiceData['key'])
            );
            $invoiceMagento = $this->invoiceRepository->getByKey($invoiceData['key']);
            if (
                $invoiceMagento->getId()
                &&
                $invoiceMagento->getOrderId() === $order->getId()
            ) {
                $this->logger->debug(
                    __("Invoice '%1' already integrated", $invoiceData['key'])
                );
                continue;
            }
            try {
                $invoice = $this->invoiceFactory->create();
                $date    = DateTime::createFromFormat(
                    'Y-m-d\TH:i:s.v',
                    $invoiceData['issueDate']
                );
                $invoice->setDanfeLink($invoiceData['danfeLink'] ?? '')
                    ->setDanfeXml($invoiceData['danfeXml'] ?? '')
                    ->setLine($invoiceData['line']);
                $invoice->setIssueDate(
                    $date
                );

                $invoice->setKey($invoiceData['key'])
                    ->setOrigin(Invoice::ORIGIN_GUBEE)
                    ->setOrderId($order->getId());
                $invoice->save();
                $totalIntegrated++;
            } catch (Throwable $e) {
                $this->logger->error(
                    __("Error saving invoice '%1': %2", $invoiceData['key'], $e->getMessage())
                );
                throw $e;
            }
        }
        $this->logger->debug(
            __("A total of '%1' invoices integrated found", $totalIntegrated)
        );

        return 0;
    }

    private function invoiceOrder(OrderInterface $order): void
    {
        // check if order is invoiced

        $invoices = $order->getInvoiceCollection();
        if ($invoices->getSize()) {
            $this->logger->debug(
                __("Order '%1' already invoiced", $order->getIncrementId())
            );
            return;
        }
        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->register();
        $invoice->save();
        $transactionSave = $this->transactionFactory->create();
        $transactionSave->addObject($invoice)
            ->addObject($invoice->getOrder())
            ->save();
        $this->logger->debug(
            __("Order '%1' was invoiced", $order->getIncrementId())
        );
        $this->addOrderHistory(
            "Order was invoiced!",
            (int) $order->getId()
        );
    }
}
