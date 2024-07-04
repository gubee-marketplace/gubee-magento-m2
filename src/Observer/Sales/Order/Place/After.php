<?php

declare(strict_types=1);

namespace Gubee\Integration\Observer\Sales\Order\Place;

use Gubee\Integration\Api\Data\ConfigInterface;
use Gubee\Integration\Api\OrderRepositoryInterface;
use Gubee\Integration\Model\Queue\Management;
use Gubee\Integration\Observer\AbstractObserver;
use Magento\Framework\Exception\NoSuchEntityException;
use Gubee\Integration\Command\Catalog\Product\Stock\SendCommand as StockSendCommand;
use Psr\Log\LoggerInterface;

class After extends AbstractObserver
{
    protected $orderRepository;

    public function __construct(
        ConfigInterface $config,
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
        if ($this->config->getEventOrder())
        {
            try {
                $mageOrder = $this->getObserver()->getEvent()->getOrder();
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
            catch(\Exception $err) {
                $this->logger->info("Order {$mageOrder->getId()} could not update gubee inventory, error: {$err->getMessage()}");
            }
        }
    }
}
