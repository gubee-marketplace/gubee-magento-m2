<?php

declare(strict_types=1);

namespace Gubee\Integration\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

use function array_merge;

class Disabled extends Field
{
    /**
     * Set "disabled" attribute to the field
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element) // phpcs:ignore
    {
        $data = $element->getData();
        $data = array_merge(
            $data ?: [],
            [
                'readonly' => 1,
                'disabled' => 'disabled',
            ]
        );

        $element->setData($data);
        return parent::_getElementHtml($element);
    }
}
