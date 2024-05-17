<?php

declare(strict_types=1);

namespace Gubee\Integration\Block\Adminhtml\System\Config\Form\Field;

use Gubee\Integration\Block\Adminhtml\System\Config\Form\Field\Fulfillment\Flag;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;

use function __;

class Fulfillment extends AbstractFieldArray
{
    /** @var Flag */
    protected $_flagRenderer;

    protected function _prepareToRender()
    {
        $this->addColumn('marketplace', ['label' => __('Marketplace'), 'class' => 'required-entry']);
        $this->addColumn('flag', ['label' => __('Flag'), 'renderer' => $this->getFlagRenderer()]);
        $this->_addAfter       = false;
        $this->_addButtonLabel = __('Add');
    }

    protected function getFlagRenderer()
    {
        if (! $this->_flagRenderer) {
            $this->_flagRenderer = $this->getLayout()->createBlock(
                Flag::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->_flagRenderer;
    }

    protected function _prepareArrayRow(DataObject $row)
    {
        $options     = [];
        $marketplace = $row->getMarketplace();
        if ($marketplace !== null) {
            $options['option_' . $this->getFlagRenderer()->calcOptionHash($marketplace)] = 'selected="selected"';
        }
        $row->setData('option_extra_attrs', $options);
    }
}
