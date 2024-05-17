<?php

declare(strict_types=1);

namespace Gubee\Integration\Model\Payment;

use Gubee\Integration\Block\Adminhtml\Sales\Order\View\Payment\Info;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Quote\Api\Data\CartInterface;

class Gubee extends AbstractMethod
{
    /**
     * Don't show the payment method on checkout page
     *
     * @var bool
     */
    protected $_canUseCheckout = false;
    protected $_code           = "gubee";
    protected $_isOffline      = true;
    protected $_infoBlockType  = Info::class;

    public function isAvailable(
        ?CartInterface $quote = null
    ) {
        return parent::isAvailable($quote);
    }
}
