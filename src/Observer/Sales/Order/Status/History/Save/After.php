<?php

namespace Gubee\Integration\Observer\Sales\Order\Status\History\Save;

use Gubee\Integration\Api\Data\ConfigInterface;
use Gubee\Integration\Command\Sales\Order\Invoice\SendCommand;
use Gubee\Integration\Model\Invoice\Parser;
use Gubee\Integration\Model\Queue\Management;
use Gubee\Integration\Observer\AbstractObserver;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

class After extends AbstractObserver
{
    /**
     * @var Parser
     */
    protected $invoiceParser;

    public function __construct(
        ConfigInterface $config,
        LoggerInterface $logger,
        Management $queueManagement,
        Parser $invoiceParser
    ) {
        parent::__construct($config, $logger, $queueManagement);
        $this->invoiceParser = $invoiceParser;
    }

    protected function process(): void
    {
        /**
         * @var \Magento\Sales\Model\Order\Status\History $statusHistory
         */
        $statusHistory = $this->getObserver()->getEvent()->getStatusHistory();
        
        $comment = $statusHistory->getComment();
        
        if ($this->invoiceParser->isItInvoice($comment) && $statusHistory->getData('origin') != 'gubee') {

            $this->queueManagement->append(
                SendCommand::class,
                [
                    'history_id' => $statusHistory->getId(),
                    'order_id' => $statusHistory->getParentId()
                ]
            );
            $this->logger->info('queue to send invoice to Gubee');
        }
        else {
            $this->logger->info('could not detect any invoice');
        }

   
    }


    public function isAllowed() : bool
    {
        
        $history = $this->getObserver()->getEvent()->getStatusHistory();
        $isGubee = false;
        /**
         * @var Order $order
         */
        if (($order = $history->getOrder()) && ($payment = $order->getPayment())) {
            $isGubee = $payment->getMethod() == 'gubee';
        }

        return parent::isAllowed() && $this->config->getInvoiceActive() && $isGubee;
    }



}