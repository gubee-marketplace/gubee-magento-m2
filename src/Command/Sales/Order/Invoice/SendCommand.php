<?php

declare(strict_types=1);

namespace Gubee\Integration\Command\Sales\Order\Invoice;

use Gubee\Integration\Api\Data\ConfigInterface;
use Gubee\Integration\Api\Data\InvoiceInterface;
use Gubee\Integration\Api\InvoiceRepositoryInterface;
use Gubee\Integration\Api\OrderRepositoryInterface as GubeeOrderRepositoryInterface;
use Gubee\Integration\Command\Sales\Order\AbstractProcessorCommand;
use Gubee\Integration\Model\Invoice;
use Gubee\Integration\Model\Invoice\Parser;
use Gubee\SDK\Resource\Sales\OrderResource;
use InvalidArgumentException;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\Data\OrderStatusHistoryInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Model\Order\Status\HistoryFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputArgument;

use function __;

class SendCommand extends AbstractProcessorCommand
{
    protected $invoiceRepository;

    protected $invoiceParser;

    protected $orderStatusHistoryRepository;

    protected $config;

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
        InvoiceRepositoryInterface $invoiceRepository,
        Parser $invoiceParser,
        OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository,
        ConfigInterface $config
    ) {
        $this->invoiceRepository = $invoiceRepository;
        $this->invoiceParser = $invoiceParser;
        $this->orderStatusHistoryRepository = $orderStatusHistoryRepository;
        $this->config = $config;

        parent::__construct(
            $eventDispatcher,
            $logger,
            $orderResource,
            $orderCollectionFactory,
            $orderRepository,
            $gubeeOrderRepository,
            $historyFactory,
            $orderManagement,
            "invoice:send",
        );
    }

    protected function configure()
    {
    
        $this->addArgument('invoice_id', InputArgument::OPTIONAL, 'Invoice ID');

        $this->addArgument('order_id', InputArgument::OPTIONAL, 'Order ID');
        $this->addArgument('history_id', InputArgument::OPTIONAL, 'History ID');
    }

    protected function beforeExecute($input, $output)
    {
        /**
         * This command is only called by the magento internal process,
         * so we don't need to check if the order is already existent.
         */
    }

    protected function afterExecute()
    {
    }

    protected function doExecute(): int
    {
        $invoice = $this->getInvoice();
        if (!is_null($invoice)) {
            if ($invoice->getOrigin() === InvoiceInterface::ORIGIN_GUBEE) {
                throw new InvalidArgumentException(
                    __(
                        "The invoice '%1' is already on Gubee",
                        $invoice->getInvoiceId()
                    )
                );
            }
            $gubeeOrder = $this->gubeeOrderRepository->getByOrderId(
                $invoice->getOrderId()
            );
            $this->orderResource->updateInvoiced(
                $gubeeOrder->getGubeeOrderId(),
                $invoice->jsonSerialize()
            );
        }
        else if ($orderHistory = $this->getOrderStatusHistory()){
            $gubeeOrder = $this->gubeeOrderRepository->getByOrderId(
                $this->input->getArgument('order_id')
            );
            $invoiceData = $this->invoiceParser->findMatch($orderHistory->getComment());

            /**
             * @var Invoice $invoice
             */
            $invoice = $this->invoiceRepository->getByOrderId($this->input->getArgument('order_id'));
            $invoice->setData($invoiceData);
            $invoice->setOrderId($this->input->getArgument('order_id'));
            $invoice->setOrigin(Invoice::ORIGIN_MAGENTO);
            $this->invoiceRepository->save($invoice);

            if ($this->config->getInvoiceCleanupXml()) // cleanup xml from history if enabled
            {
                $orderHistory->setData('origin', 'gubee');
                $orderHistory->setComment($this->invoiceParser->cleanupXml($orderHistory->getComment()));
                $this->orderStatusHistoryRepository->save($orderHistory);
            }
        
        }
        return 0;
    }

    private function getInvoice(): ?InvoiceInterface
    {
        $invoiceId = $this->input->getArgument('invoice_id');
        if (is_null($invoiceId)) {
            return null;
        }
        return $this->invoiceRepository->get($invoiceId);
    }

    private function getOrderStatusHistory(): ?OrderStatusHistoryInterface
    {
        $historyId = $this->input->getArgument('history_id');
        if (is_null($historyId)) {
            return null;
        }

        return $this->orderStatusHistoryRepository->get($historyId);
    } 

    public function getPriority(): int
    {
        return 150;
    }
}
