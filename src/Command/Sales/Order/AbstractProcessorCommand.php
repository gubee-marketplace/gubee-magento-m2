<?php

declare(strict_types=1);

namespace Gubee\Integration\Command\Sales\Order;

use Gubee\Integration\Api\OrderRepositoryInterface as GubeeOrderRepositoryInterface;
use Gubee\Integration\Command\AbstractCommand;
use Gubee\Integration\Command\Sales\Order\Processor\CreatedCommand;
use Gubee\SDK\Resource\Sales\OrderResource;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Status\HistoryFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function sprintf;

abstract class AbstractProcessorCommand extends AbstractCommand
{
    protected OrderRepositoryInterface $orderRepository;
    protected CollectionFactory $orderCollectionFactory;
    protected OrderResource $orderResource;
    protected HistoryFactory $historyFactory;
    protected OrderManagementInterface $orderManagement;
    protected GubeeOrderRepositoryInterface $gubeeOrderRepository;

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
        $this->gubeeOrderRepository   = $gubeeOrderRepository;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderManagement        = $orderManagement;
        $this->orderRepository        = $orderRepository;
        $this->orderResource          = $orderResource;
        $this->historyFactory         = $historyFactory;
        parent::__construct(
            $eventDispatcher,
            $logger,
            "sales:order:processor:" . $name
        );
    }

    protected function configure()
    {
        $this->addArgument('order_id', InputArgument::REQUIRED, 'Order increment ID');
    }

    protected function beforeExecute($input, $output)
    {
        /**
         * Any non created command should validate if order is already created
         * if not, it should execute the created command
         */
        if (! $this instanceof CreatedCommand) {
            $orderId = $input->getArgument('order_id');
            $order   = $this->getOrder($orderId);
            if (! $order) {
                $inputTmp  = ObjectManager::getInstance()->create(ArrayInput::class, [
                    'parameters' => [
                        'order_id' => $orderId,
                    ],
                ]);
                $outputTmp = ObjectManager::getInstance()->create(BufferedOutput::class);
                ObjectManager::getInstance()->create(CreatedCommand::class)
                    ->run($inputTmp, $outputTmp);
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->beforeExecute($input, $output);
        $result = parent::execute($input, $output);
        if ($result !== 0) {
            return $result;
        }

        $this->afterExecute();

        return $result;
    }

    protected function afterExecute()
    {
        $orderId = $this->getInput()->getArgument('order_id');
        $order   = $this->getOrder($orderId);
        if (! $order) {
            $this->logger->error(sprintf("Order with increment ID %s not found", $orderId));
            return 1;
        }

        $orderContent = $this->orderResource->loadByOrderId($orderId);
        if (! $orderContent) {
            $this->logger->error(sprintf("Order with increment ID %s not found in Gubee", $orderId));
            return 1;
        }
        $payment = $order->getPayment();
        $payment->setAdditionalInformation(
            'gubee_order',
            $orderContent
        );
        $order->setPayment($payment);
        $this->logger->debug(sprintf("Order with increment ID %s loaded from Gubee", $orderId));
        $this->orderRepository->save($order);
        $this->logger->debug(sprintf("Order with increment ID %s updated", $orderId));
    }

    public function getOrder(string $incrementId): ?OrderInterface
    {
        try {
            $gubeeOrder = $this->gubeeOrderRepository->getByGubeeOrderId($incrementId);
        } catch (Throwable $e) {
            return null;
        }
        $order = $this->orderCollectionFactory->create()
            ->addFieldToFilter(
                'entity_id',
                $gubeeOrder->getOrderId()
            )
            ->getFirstItem();
        if (! $order->getId()) {
            return null;
        }

        return $order;
    }

    public function addOrderHistory(string $message, int $orderId)
    {
        $history = $this->historyFactory->create();
        $history->setComment(
            sprintf("[Gubee Integration] %s", (string) $message)
        );
        $history->setParentId($orderId);
        $history->setIsCustomerNotified(false);
        $history->setIsVisibleOnFront(true);
        $history->save();
    }
}
