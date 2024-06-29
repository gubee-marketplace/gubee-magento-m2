<?php

namespace Gubee\Integration\Plugin\Sales\Order\Email\Sender;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender as OrderCommentSenderSender;
use Magento\Sales\Model\Order\Invoice;

class OrderCommentSender extends PluginAbstract 
{
    public function aroundSend(OrderCommentSenderSender $subject, callable $proceed, Order $order, $notify = true, $comment = '')
    {
        if ($this->shouldPreventExecution($order->getPayment()->getMethod())) {
            return;
        }

        return $proceed($order, $notify, $comment);
    }
}