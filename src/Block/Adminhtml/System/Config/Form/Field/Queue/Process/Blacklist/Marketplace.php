<?php

declare(strict_types=1);

namespace Gubee\Integration\Block\Adminhtml\System\Config\Form\Field\Queue\Process\Blacklist;

use Gubee\Integration\Block\Adminhtml\System\Config\Form\Field\Queue\Process\Blacklist;

use function __;

class Marketplace extends Blacklist
{
    protected function _prepareToRender()
    {
        $this->addColumn('marketplace', ['label' => __('Marketplace'), 'class' => 'required-entry']);
        return parent::_prepareToRender();
    }
}
