<?php

namespace Gubee\Integration\Plugin\Sales\Order\Shipment\Sender;

use Gubee\Integration\Plugin\Sales\Order\Email\Sender\PluginAbstract;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentCommentCreationInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\Order\Shipment\Sender\EmailSender as SenderEmailSender;

class EmailSender extends PluginAbstract
{
    public function beforeSend(
        SenderEmailSender $subject,
        OrderInterface $order,
        ShipmentInterface $shipment,
        ShipmentCommentCreationInterface $comment = null,
        $forceSyncMode = false,
        callable $proceed
    ) {
        if ($this->shouldPreventExecution($order->getPayment()->getMethod()))
        {
            return;
        }

        return $proceed($order, $shipment, $comment, $forceSyncMode);
    }
}