<?php

declare(strict_types=1);

namespace Gubee\Integration\Observer\Sales\Order\Shipment;

use Exception;
use Gubee\Integration\Model\Config;
use Gubee\Integration\Model\InvoiceRepository;
use Gubee\Integration\Model\Queue\Management;
use Gubee\Integration\Model\ResourceModel\Invoice\CollectionFactory;
use Gubee\Integration\Observer\AbstractObserver;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use Throwable;

use function __;

class NewShipment extends AbstractObserver
{
    protected RequestInterface $request;
    protected InvoiceRepository $invoiceRepository;
    protected Registry $registry;
    protected ResponseFactory $responseFactory;
    protected UrlInterface $url;
    protected CollectionFactory $invoiceCollectionFactory;
    protected ManagerInterface $messageManager;

    public function __construct(
        Config $config,
        LoggerInterface $logger,
        Management $queueManagement,
        RequestInterface $request,
        InvoiceRepository $invoiceRepository,
        CollectionFactory $invoiceCollectionFactory,
        Registry $registry,
        ResponseFactory $responseFactory,
        UrlInterface $url,
        ManagerInterface $messageManager
    ) {
        parent::__construct($config, $logger, $queueManagement);
        $this->responseFactory          = $responseFactory;
        $this->url                      = $url;
        $this->request                  = $request;
        $this->invoiceCollectionFactory = $invoiceCollectionFactory;
        $this->registry                 = $registry;
        $this->invoiceRepository        = $invoiceRepository;
        $this->messageManager           = $messageManager;
    }

    protected function process(): void
    {
        $params = $this->request->getParams();
        try {
            $collection = $this->invoiceCollectionFactory->create();
            $collection->addFieldToFilter('order_id', $params['order_id']);
            if ($collection->getSize() < 1) {
                throw new Exception(
                    __("Every Gubee shipment requires a corresponding invoice."
                        . " Since the current order lacks an invoice, please attach"
                        . " a Gubee Invoice to proceed.")->__toString()
                );
            }
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->getObserver()->getControllerAction()->getResponse()
                ->setRedirect(
                    $this->url->getUrl('sales/order/view', ['order_id' => $params['order_id']]),
                    301
                );
            return;
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
