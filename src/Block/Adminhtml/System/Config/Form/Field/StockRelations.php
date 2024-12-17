<?php
 
namespace Gubee\Integration\Block\Adminhtml\System\Config\Form\Field;

use Gubee\Integration\Block\Adminhtml\System\Config\Form\Field\StockRelations\Stock;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
 
class StockRelations extends AbstractFieldArray
{
    protected $_sourceRenderer; 

    protected function _prepareToRender()
    {
        $this->addColumn('stock_id', ['label' => __('Magento Stock Source'), 'class' => 'required-entry', 'renderer' => $this->getSourceRenderer()]);
        $this->addColumn('gubee_code', ['label' => __('Gubee Distribution Center Code'), 'class' => 'required-entry']);        
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add New');
    }

    protected function getSourceRenderer()
    {
        if (! $this->_sourceRenderer) {
            $this->_sourceRenderer = $this->getLayout()->createBlock(
                Stock::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->_sourceRenderer;
    }

    protected function _prepareArrayRow(DataObject $row)
    {
        $options     = [];
        $marketplace = $row->getStockId();
        if ($marketplace !== null) {
            $options['option_' . $this->getSourceRenderer()->calcOptionHash($marketplace)] = 'selected="selected"';
        }
        $row->setData('option_extra_attrs', $options);
    }
}