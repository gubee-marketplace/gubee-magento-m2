<?php

declare(strict_types=1);

namespace Gubee\Integration\Block\Adminhtml\System\Config\Form\Field\StockRelations;

use Gubee\Integration\Model\Source\System\Config\Inventory\Stock as StockOptions;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Magento\Inventory\Model\ResourceModel\Source as ResourceModelSource;

/**
 * Boolean renderer for fulfillment flag
 */
class Stock extends Select
{
    /** @var StockOptions */
    protected $stockOptions;

    /**
     * @param array $data
     */
    public function __construct(
        Context $context,
        StockOptions $stockOptions,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->stockOptions = $stockOptions;
    }
    
    /**
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }
    
    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml()
    {
        if (! $this->getOptions()) {
            foreach ($this->stockOptions->toOptionArray() as $option) {
                $this->addOption($option['value'], $option['label']);
            }
        }
        return parent::_toHtml();
    }
}
