<?php

namespace Gubee\Integration\Plugin\Sales\Order\Email\Sender;

use Magento\Sales\Model\Order\Email\Sender\ShipmentSender as SenderShipmentSender;
use Magento\Sales\Model\Order\Shipment;

class ShipmentSender extends PluginAbstract 
{
    public function aroundSend(SenderShipmentSender $subject, callable $proceed, Shipment $shipment, $forceSyncMode = false)
    {
        if ($this->shouldPreventExecution($shipment->getOrder()->getPayment()->getMethod())) {
            return;
        }

        return $proceed($shipment, $forceSyncMode);
    }
}