<?php

declare(strict_types=1);

namespace Gubee\Integration\Command\Gubee\Notification\Queue;

use Gubee\Integration\Api\Queue\ManagementInterface;
use Gubee\Integration\Command\AbstractCommand;
use Gubee\Integration\Command\Sales\Order\Processor\CanceledCommand;
use Gubee\Integration\Command\Sales\Order\Processor\CreatedCommand;
use Gubee\Integration\Command\Sales\Order\Processor\DeliveredCommand;
use Gubee\Integration\Command\Sales\Order\Processor\InvoicedCommand;
use Gubee\Integration\Command\Sales\Order\Processor\PaidCommand;
use Gubee\Integration\Command\Sales\Order\Processor\PayedCommand;
use Gubee\Integration\Command\Sales\Order\Processor\RejectedCommand;
use Gubee\Integration\Command\Sales\Order\Processor\ShippedCommand;
use Gubee\SDK\Resource\ResultPager;
use Gubee\SDK\Resource\Sales\Order\Queue\NotificationResource;
use Magento\Framework\Event\ManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Exception\LogicException;

class ConsumeCommand extends AbstractCommand
{
    protected ManagementInterface $management;
    protected NotificationResource $notificationResource;
    protected ResultPager $resultPager;

    /**
     * @param string|null $name The name of the command; passing null means it must be set in configure()
     * @throws LogicException When the command name is empty.
     */
    public function __construct(
        ManagerInterface $eventDispatcher,
        LoggerInterface $logger,
        ManagementInterface $management,
        NotificationResource $notificationResource,
        ResultPager $resultPager
    ) {
        $this->resultPager          = $resultPager;
        $this->notificationResource = $notificationResource;
        $this->management           = $management;
        parent::__construct(
            $eventDispatcher,
            $logger,
            "notification:queue:consume"
        );
    }

    protected function configure(): void
    {
        $this->setName("notification:queue:consume");
        $this->setDescription("Consume notification queue");
    }

    protected function doExecute(): int
    {
        foreach (
            [
                'getCreatedOrders'   => [
                    'processor' => CreatedCommand::class,
                    'consumer'  => 'removeCreatedOrder',
                ],
                'getCanceledOrders'  => [
                    'processor' => CanceledCommand::class,
                    'consumer'  => 'removeCanceledOrder',
                ],
                'getDeliveredOrders' => [
                    'processor' => DeliveredCommand::class,
                    'consumer'  => 'removeDeliveredOrder',
                ],
                'getInvoicedOrders'  => [
                    'processor' => InvoicedCommand::class,
                    'consumer'  => 'removeInvoicedOrder',
                ],
                'getPaidOrders'      => [
                    'processor' => PaidCommand::class,
                    'consumer'  => 'removePaidOrder',
                ],
                'getPayedOrders'     => [
                    'processor' => PayedCommand::class,
                    'consumer'  => 'removePayedOrder',
                ],
                'getRejectedOrders'  => [
                    'processor' => RejectedCommand::class,
                    'consumer'  => 'removeRejectedOrder',
                ],
                'getShippedOrders'   => [
                    'processor' => ShippedCommand::class,
                    'consumer'  => 'removeShippedOrder',
                ],
            ] as $type => $command
        ) {
            $this->resultPager->fetchAllWithCallback(
                $this->notificationResource,
                $type,
                function ($items) use ($command) {
                    $this->queueItems(
                        $items,
                        $command['processor'],
                        $command['consumer']
                    );
                },
                [
                    0,
                    10,
                ]
            );
        }

        return 0;
    }

    protected function queueItems(
        array $items,
        string $command,
        string $consumer
    ) {
        $items = $items['_embedded'] ?? [];
        foreach ($items['orders'] ?? [] as $item) {
            $this->management->append(
                $command,
                ['order_id' => $item['id'] ?? $item['orderId']]
            );
            $this->notificationResource->$consumer($item['id'] ?? $item['orderId']);
        }
    }
}
