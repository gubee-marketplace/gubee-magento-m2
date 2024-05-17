<?php

declare(strict_types=1);

namespace Gubee\Integration\Observer\Sales\Order\Shipment\Save;

use Gubee\Integration\Command\Sales\Order\Shipment\SendCommand;
use Gubee\Integration\Observer\AbstractObserver;
use Gubee\Integration\Model\Config;
use Gubee\Integration\Model\Queue\Management;
use Psr\Log\LoggerInterface;
use Gubee\Integration\Api\OrderRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class After extends AbstractObserver
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

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
        $shipment = $this->getObserver()->getShipment();
        $mageOrder    = $shipment->getOrder();
        try {
            $order = $this->orderRepository->getByOrderId($mageOrder->getId());
                
            $this->queueManagement->append(
                SendCommand::class,
                [
                    'order_id' => $order->getGubeeOrderId(),
                ]
            );
        }
        catch (NoSuchEntityException $exception)
        {
            $this->logger->info("Order {$mageOrder->getId()} is not integrated with gubee");
        }
    
    }
}
