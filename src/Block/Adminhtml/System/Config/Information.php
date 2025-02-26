<?php
declare(strict_types=1);

namespace Gubee\Integration\Block\Adminhtml\System\Config;

use Gubee\Integration\Model\VersionFetcherLegacy;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class Information extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    protected $versionFetcher;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        VersionFetcherLegacy $versionFetcherLegacy,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data, $secureRenderer);
        $this->versionFetcher = $versionFetcherLegacy;
    }
    /**
     * Render fieldset html
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $html = $this->_getHeaderHtml($element);
        $this->setContent(__("Gubee Integration, version: <strong>%1</strong>", $this->getVersion()));
        
        $html .= $this->getContent();
        $html .= $this->_getFooterHtml($element);
        
        $html = preg_replace('(onclick=\"Fieldset.toggleCollapse.*?\")', '', $html);

        return $html;
    }

    protected function getVersion()
    {
        try {
            if (class_exists(\Composer\InstalledVersions::class)) {
                return \Composer\InstalledVersions::getVersion('gubee-marketplace/integration-module');
            }
            else {
                return $this->versionFetcher->getModuleVersion('Gubee_Integration') ?? $this-> __('Could not find version');
            }
        }
        catch (\Exception $e) {
            die($e->getMessage());
            return __('via app/code');    
        }
    }
}