<?php

declare(strict_types=1);

namespace Gubee\Integration\Block\Adminhtml\System\Config\Form\Field\Fulfillment;

use Magento\Config\Model\Config\Source\Yesno;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;

/**
 * Boolean renderer for fulfillment flag
 */
class Flag extends Select
{
    /** @var Yesno */
    protected $yesNo;

    /**
     * @param array $data
     */
    public function __construct(
        Context $context,
        Yesno $yesNo,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->yesNo = $yesNo;
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml()
    {
        if (! $this->getOptions()) {
            foreach ($this->yesNo->toOptionArray() as $option) {
                $this->addOption($option['value'], $option['label']);
            }
        }
        return parent::_toHtml();
    }
}
