<?php

declare(strict_types=1);

namespace Gubee\Integration\Block\Adminhtml\Invoice\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

use function __;
use function sprintf;

class BackButton extends GenericButton implements ButtonProviderInterface
{
    protected Registry $registry;

    public function __construct(
        Context $context,
        Registry $registry
    ) {
        parent::__construct($context);
        $this->registry = $registry;
    }

    /**
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label'      => __('Back'),
            'on_click'   => sprintf("location.href = '%s';", $this->getBackUrl()),
            'class'      => 'back',
            'sort_order' => 10,
        ];
    }

    /**
     * Get URL for back (reset) button
     *
     * @return string
     */
    public function getBackUrl()
    {
        // redirect to order view page
        $orderId = $this->registry->registry('gubee_integration_invoice')->getOrderId();
        return $this->getUrl('sales/order/view', ['order_id' => $orderId]);
    }
}
