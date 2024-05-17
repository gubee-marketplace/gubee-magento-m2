<?php

declare(strict_types=1);

namespace Gubee\Integration\Observer\Gubee\Command\Execute;

use Exception;
use Gubee\Integration\Api\InvoiceRepositoryInterface;
use Gubee\Integration\Api\OrderRepositoryInterface;
use Gubee\Integration\Command\Sales\Order\AbstractProcessorCommand;
use Gubee\Integration\Command\Sales\Order\Invoice\SendCommand;
use Gubee\Integration\Model\Config;
use Gubee\Integration\Model\Queue\Management;
use Gubee\Integration\Observer\AbstractObserver;
use Gubee\SDK\Resource\Sales\OrderResource;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;

use function __;
use function in_array;
use function is_subclass_of;

class Before extends AbstractObserver
{
    protected Registry $registry;
    protected OrderResource $orderResource;

    public function __construct(
        Config $config,
        LoggerInterface $logger,
        Management $queueManagement,
        Registry $registry
    ) {
        parent::__construct($config, $logger, $queueManagement);
        $this->registry = $registry;
    }

    protected function process(): void
    {
        $rules = $this->config->getFulfilmentRules();
        if (empty($rules) || $rules == []) {
            $this->logger->debug(
                __("No rules found for the fulfilment grid. Skipping...")
            );
            return;
        }
        $message = $this->registry->registry('gubee_current_message');
        if (! $message) {
            return;
        }
        if (
            ! is_subclass_of(
                $message->getCommand(),
                AbstractProcessorCommand::class
            )
        ) {
            return;
        }

        $orderResource = ObjectManager::getInstance()
            ->get(OrderResource::class);

        if (
            $message->getCommand() == SendCommand::class
        ) {
            $invoice = ObjectManager::getInstance()
                ->get(
                    InvoiceRepositoryInterface::class
                );
            $invoice = $invoice->get($message->getPayload()['invoice_id']);
            if (! $invoice) {
                throw new Exception(
                    __("Invoice not found")->__toString()
                );
            }

            $order = ObjectManager::getInstance()
                ->get(OrderRepositoryInterface::class);
            $order = $order->getByOrderId(
                $invoice->getOrderId()
            );
            $order = $orderResource->loadByOrderId(
                $order->getGubeeOrderId()
            );
        } else {
            $order = $orderResource->loadByOrderId(
                $message->getPayload()['order_id']
            );
        }

        if (! $this->isFulfilment($order)) {
            return;
        }
        if (! in_array($order['plataform'], $rules)) {
            throw new Exception(
                __("Order not allowed to be fulfilled")->__toString()
            );
        }
    }

    protected function isFulfilment($order)
    {
        $isFullfilment = false;
        foreach ($order['items'] as $item) {
            if (! isset($item['fulfillment'])) {
                continue;
            }
            if ($item['fulfillment'] == true) {
                $isFullfilment = true;
                break;
            }
        }

        return $isFullfilment;
    }

    /**
     * Validate if the observer is allowed to run
     */
    protected function isAllowed(): bool
    {
        if (! $this->getConfig()->getFulfilmentEnable()) {
            return false;
        }

        return parent::isAllowed();
    }
}
