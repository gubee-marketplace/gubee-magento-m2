<?php

declare(strict_types=1);

namespace Gubee\Integration\Block\Adminhtml\Sales\Order;

use Gubee\Integration\Model\ResourceModel\Invoice\CollectionFactory;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Shipping\Block\Adminhtml\Order\Tracking as OrderTracking;
use Magento\Shipping\Model\Config;

class Tracking extends OrderTracking
{
    protected CollectionFactory $invoiceCollectionFactory;

    public function __construct(
        Context $context,
        Config $shippingConfig,
        Registry $registry,
        CollectionFactory $invoiceCollectionFactory,
        array $data = []
    ) {
        $this->invoiceCollectionFactory = $invoiceCollectionFactory;
        parent::__construct($context, $shippingConfig, $registry, $data);
    }

    public function _prepareLayout()
    {
        if ($this->getOrder()->getPayment()->getMethod() == 'gubee') {
            $this->setTemplate('Gubee_Integration::order/tracking.phtml');
        }
        parent::_prepareLayout();
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
}
