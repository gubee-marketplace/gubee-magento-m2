<?php

declare(strict_types=1);

namespace Gubee\Integration\Observer\Sales\Invoice\Save;

use Gubee\Integration\Api\Data\InvoiceInterface;
use Gubee\Integration\Command\Sales\Order\Invoice\SendCommand;
use Gubee\Integration\Observer\AbstractObserver;

class After extends AbstractObserver
{
    protected function process(): void
    {
        $invoice = $this->getObserver()->getObject();
        if ($invoice->getOrigin() !== InvoiceInterface::ORIGIN_GUBEE) {
            $this->queueManagement->append(
                SendCommand::class,
                ['invoice_id' => $invoice->getId()]
            );
            $this->logger->info('queue to send invoice to Gubee');
        } else {
            $this->logger->debug('Invoice origin is Gubee, no need to send again');
        }
    }

    protected function isAllowed(): bool
    {
        return $this->getObserver()->getObject() instanceof InvoiceInterface
        && parent::isAllowed();
    }
}
