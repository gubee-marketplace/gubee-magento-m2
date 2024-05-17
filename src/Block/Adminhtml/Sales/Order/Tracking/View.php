<?php

declare(strict_types=1);

namespace Gubee\Integration\Block\Adminhtml\Sales\Order\Tracking;

use Gubee\Integration\Api\InvoiceRepositoryInterface;
use Gubee\Integration\Model\ResourceModel\Invoice\CollectionFactory;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Shipping\Helper\Data as ShippingHelper;
use Magento\Shipping\Model\CarrierFactory;
use Magento\Shipping\Model\Config;

class View extends \Magento\Shipping\Block\Adminhtml\Order\Tracking\View
{
    protected InvoiceRepositoryInterface $invoiceRepository;
    protected CollectionFactory $invoiceCollectionFactory;

    public function __construct(
        Context $context,
        Config $shippingConfig,
        Registry $registry,
        CarrierFactory $carrierFactory,
        InvoiceRepositoryInterface $invoiceRepository,
        CollectionFactory $invoiceCollectionFactory,
        array $data = [],
        ?ShippingHelper $shippingHelper = null
    ) {
        parent::__construct(
            $context,
            $shippingConfig,
            $registry,
            $carrierFactory,
            $data,
            $shippingHelper
        );
        $this->invoiceRepository        = $invoiceRepository;
        $this->invoiceCollectionFactory = $invoiceCollectionFactory;
    }

    protected function _construct()
    {
        parent::_construct();
        if ($this->getOrder()->getPayment()->getMethod() == 'gubee') {
            $this->_template = 'Gubee_Integration::order/tracking/view.phtml';
        }
    }

    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_shipment')
            ->getOrder();
    }

    public function getOrderId()
    {
        return $this->getOrder()->getId();
    }

    public function getInvoices()
    {
        return $this->invoiceCollectionFactory->create()
            ->addFieldToFilter('order_id', $this->getOrderId());
    }

    public function getInvoiceRepository(): InvoiceRepositoryInterface
    {
        return $this->invoiceRepository;
    }

    public function getInvoiceById($id)
    {
        return $this->invoiceCollectionFactory->create()
            ->addFieldToFilter('order_id', $this->getOrderId())
            ->addFieldToFilter('shipment_id', $id)
            ->getFirstItem();
    }
}
