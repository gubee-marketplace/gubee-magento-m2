<?php

declare(strict_types=1);

namespace Gubee\Integration\Block\Adminhtml\Sales\Order\View\Payment;

use function json_encode;

class Info extends \Magento\Payment\Block\Info
{
    protected $_template = 'Gubee_Integration::payment/info.phtml';

    public function getOrderInfoJson()
    {
        $data = $this->getOrder()
            ->getPayment()
            ->getData(
                'additional_information/gubee_order'
            );
        return json_encode($data);
    }

    /**
     * Retrieve order model object
     *
     * @return Order
     * @throws LocalizedException
     */
    public function getOrder()
    {
        return $this->getInfo()->getOrder();
    }
}
