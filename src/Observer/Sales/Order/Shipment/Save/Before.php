<?php

declare(strict_types=1);

namespace Gubee\Integration\Observer\Sales\Order\Shipment\Save;

use Exception;
use Gubee\Integration\Model\Config;
use Gubee\Integration\Model\InvoiceRepository;
use Gubee\Integration\Model\Queue\Management;
use Gubee\Integration\Observer\AbstractObserver;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;

use function __;
use function in_array;

class Before extends AbstractObserver
{
    protected RequestInterface $request;
    protected InvoiceRepository $invoiceRepository;
    protected Registry $registry;
    protected ManagerInterface $messageManager;

    public function __construct(
        Config $config,
        LoggerInterface $logger,
        Management $queueManagement,
        RequestInterface $request,
        InvoiceRepository $invoiceRepository,
        Registry $registry,
        ManagerInterface $messageManager
    ) {
        parent::__construct($config, $logger, $queueManagement);
        $this->request           = $request;
        $this->registry          = $registry;
        $this->invoiceRepository = $invoiceRepository;
        $this->messageManager    = $messageManager;
    }

    protected function process(): void
    {
        $params = $this->request->getParams();
        try {
            if (isset($params['tracking'])) {

                foreach ($params['tracking'] as $tracking) {
                    if (! isset($tracking['shipment_key'])) {
                        throw new Exception(
                            __("Each shipment of Gubee must be associated with a invoice key.")->__toString()
                        );
                    }
                    $invoiceKeys = [];
                    foreach ($params['tracking'] as $tracking) {
                        if (in_array($tracking['shipment_key'], $invoiceKeys)) {
                            throw new Exception(
                                __("The same invoice key cannot be used in more than one shipment.")->__toString()
                            );
                        }
                        $invoiceKeys[] = $tracking['shipment_key'];
                    }
                }
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate if the observer is allowed to run
     */
    protected function isAllowed(): bool
    {
        $order = $this->registry->registry('current_shipment')
            ->getOrder();
        if ($order->getPayment()->getMethod() !== 'gubee') {
            return false;
        }

        return parent::isAllowed();
    }
}
