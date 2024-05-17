<?php

declare(strict_types=1);

namespace Gubee\Integration\Block\Adminhtml\Sales\Order\View\Tab\Gubee\Invoice\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

use function __;

class Origin extends AbstractRenderer
{
    public function render(DataObject $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());

        switch ($value) {
            case 0:
                $value = __('Magento');
                break;
            case 1:
                $value = __('Gubee');
                break;
        }

        return $value;
    }
}
