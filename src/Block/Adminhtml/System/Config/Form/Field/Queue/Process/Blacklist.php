<?php

declare(strict_types=1);

namespace Gubee\Integration\Block\Adminhtml\System\Config\Form\Field\Queue\Process;

use Gubee\Integration\Block\Adminhtml\System\Config\Form\Field\Fulfillment\Flag;
use Gubee\Integration\Block\Adminhtml\System\Config\Form\Field\Queue\Process\Column\Command;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;

use function __;

class Blacklist extends AbstractFieldArray
{
    /** @var Flag */
    protected $_flagRenderer;

    protected $_commandRenderer;

    protected function _prepareToRender()
    {
        $this->addColumn('command', ['label' => __('Queue'), 'renderer' => $this->getCommandRenderer()]);
        $this->addColumn('flag', ['label' => __('Should skip?'), 'renderer' => $this->getBooleanFlag()]);
        $this->_addAfter       = false;
        $this->_addButtonLabel = __('Add');
    }

    protected function getCommandRenderer()
    {
        if (! $this->_commandRenderer) {
            $this->_commandRenderer = $this->getLayout()->createBlock(
                Command::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->_commandRenderer;
    }

    protected function getBooleanFlag()
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
        $options = [];
        $command = $row->getCommand();
        if ($command !== null) {
            $options['option_' . $this->getCommandRenderer()->calcOptionHash($command)] = 'selected="selected"';
        }
        $flag = $row->getFlag();
        if ($flag !== null) {
            $options['option_' . $this->getBooleanFlag()->calcOptionHash($flag)] = 'selected="selected"';
        }
        $row->setData('option_extra_attrs', $options);
    }
}
