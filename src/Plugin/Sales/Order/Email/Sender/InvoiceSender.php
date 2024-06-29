<?php

namespace Gubee\Integration\Plugin\Sales\Order\Email\Sender;

use Magento\Sales\Model\Order\Email\Sender\InvoiceSender as SenderInvoiceSender;
use Magento\Sales\Model\Order\Invoice;

class InvoiceSender extends PluginAbstract 
{
    public function aroundSend(SenderInvoiceSender $subject, callable $proceed, Invoice $invoice, $forceSyncMode = false)
    {
        if ($this->shouldPreventExecution($invoice->getOrder()->getPayment()->getMethod())) {
            return;
        }

        return $proceed($invoice, $forceSyncMode);
    }
}