<?php

declare(strict_types=1);

namespace Gubee\Integration\Command\Sales\Order\Shipment;

use DateTime;
use Gubee\Integration\Api\OrderRepositoryInterface as GubeeOrderRepositoryInterface;
use Gubee\Integration\Command\Sales\Order\AbstractProcessorCommand;
use Gubee\Integration\Model\Config;
use Gubee\Integration\Service\Model\Catalog\Product\Variation;
use Gubee\SDK\Resource\Sales\OrderResource;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Status\HistoryFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Exception\LogicException;

use function __;
use function count;
use function end;
use function explode;
use function json_decode;
use function sprintf;
use function strpos;

class SendCommand extends AbstractProcessorCommand
{
    protected Config $config;

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
        HistoryFactory $historyFactory,
        GubeeOrderRepositoryInterface $gubeeOrderRepository,
        OrderManagementInterface $orderManagement,
        Config $config
    ) {
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
            "shipment:send"
        );
    }

    protected function doExecute(): int
    {
        $orderId   = $this->getInput()->getArgument('order_id');
        $order     = $this->getOrder($orderId);
        $trackings = $order->getTracksCollection();
        $this->logger->debug(
            sprintf(
                "Order '%s' has '%d' tracking(s)",
                $order->getIncrementId(),
                count($trackings)
            )
        );
        foreach ($trackings as $tracking) {
            $items      = $tracking->getShipment()->getItems();
            $gubeeItems = [];
            foreach ($items as $shipmentItem) {
                $orderItem      = $shipmentItem->getOrderItem();
                $additionalData = json_decode(
                    $orderItem->getAdditionalData(),
                    true
                );
                if (!isset($gubeeItems[$additionalData['skuId']])) 
                {
                    $gubeeItems[$additionalData['skuId']] = $additionalData['qty'];
                }
            }

            $gubeeItemsToSend = [];
            foreach ( $gubeeItems as $gSku => $gQty ) {
                $gubeeItemsToSend[] = [
                    'qty' => $gQty,
                    'skuId' => $gSku
                ];
            }

            $trackingGubee = [
                'code'                => sprintf("%s:%s", $order->getIncrementId(), $tracking->getId()),
                'items'               => $gubeeItemsToSend,
                'estimatedDeliveryDt' => (new DateTime(
                    'now + '
                    . $this->config->getDefaultDeliveryTime()
                    . ' days'
                ))->format('Y-m-d\TH:i:s.v'),
                'transport'           => [
                    'carrier'      => $tracking->getTitle(),
                    'link'         => "https://gubee.com.br/",
                    'method'       => $tracking->getCarrierCode(),
                    'trackingCode' => $tracking->getTrackNumber(),
                ],
            ];
            $this->orderResource->updateShipped($orderId, $trackingGubee);
            $this->logger->info(
                sprintf(
                    "Order '%s' has been shipped with tracking '%s'",
                    $order->getIncrementId(),
                    $tracking->getTrackNumber()
                )
            );
        }

        $this->logger->info(
            sprintf(
                "Order '%s' has been shipped, with '%d' tracking(s)",
                $order->getIncrementId(),
                count($trackings)
            )
        );

        return 0;
    }

    protected function getSku(
        string $sku
    ): string {
        $this->logger->debug(
            __("Getting product with SKU '%1'", $sku)
        );
        if (strpos($sku, Variation::SEPARATOR) !== false) {
            $sku = explode(Variation::SEPARATOR, $sku);
            $sku = end($sku);
        }

        return $sku;
    }

    public function getPriority(): int
    {
        return 100;
    }
}
