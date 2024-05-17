<?php

declare(strict_types=1);

namespace Gubee\Integration\Observer\Sales\Order\Place;

use Gubee\Integration\Api\OrderRepositoryInterface;
use Gubee\Integration\Command\Sales\Order\Cancel\SendCommand;
use Gubee\Integration\Model\Config;
use Gubee\Integration\Model\Queue\Management;
use Gubee\Integration\Observer\AbstractObserver;
use Magento\Framework\Exception\NoSuchEntityException;
use Gubee\Integration\Command\Catalog\Product\Stock\SendCommand as StockSendCommand;
use Psr\Log\LoggerInterface;

class After extends AbstractObserver
{
    protected OrderRepositoryInterface $orderRepository;

    public function __construct(
        Config $config,
        LoggerInterface $logger,
        Management $queueManagement,
        OrderRepositoryInterface $orderRepository
    ) {
        parent::__construct($config, $logger, $queueManagement);
        $this->orderRepository = $orderRepository;
    }

    protected function process(): void
    {
        /**
         * @var \Magento\Sales\Model\Order $mageOrder
         */
        try {
            $mageOrder = $this->getObserver()->getEvent()->getOrder();
            if ($mageOrder->getPayment()->getMethod() != "gubee") { //if order is not gubee
                /**
                * @var \Magento\Sales\Model\Order\Item $item
                */
                foreach ($mageOrder->getAllVisibleItems() as $item) //update product stock in gubee
                {
                    $this->queueManagement->append(
                        StockSendCommand::class,
                        [
                            "sku" => $item->getSku()
                        ],
                        (int) $item->getProductId()
                    );
                }
            }
        }
        catch(\Exception $err) {
            $this->logger->info("Order {$mageOrder->getId()} could not update gubee inventory, error: {$err->getMessage()}");
        }
    }
}
