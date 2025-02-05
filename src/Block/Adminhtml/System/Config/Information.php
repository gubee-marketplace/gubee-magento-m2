<?php
declare(strict_types=1);

namespace Gubee\Integration\Block\Adminhtml\System\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;

class Information extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * Render fieldset html
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $html = $this->_getHeaderHtml($element);
        $this->setContent(__("Gubee Integration version %s", $this->getVersion()));
        
        $html .= $this->getContent();
        $html .= $this->_getFooterHtml($element);
        
        $html = preg_replace('(onclick=\"Fieldset.toggleCollapse.*?\")', '', $html);

        return $html;
    }

    protected function getVersion()
    {
        return \Composer\InstalledVersions::getVersion('gubee/module-integration');
    }
}