<?php

declare (strict_types = 1);

namespace Gubee\Integration\Ui\Component\Sales\Order\Listing\Column;

use Exception;
use Gubee\SDK\Resource\PlatformResource;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class Marketplace extends Column {
    protected static $platform = [];
    protected PlatformResource $platformResource;

    public function __construct(
        PlatformResource $platformResource,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->platformResource = $platformResource;
    }

    /**
     * Prepare status column
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource) {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (!isset($item[$this->getData('name')])) {
                    continue;
                }
                $item[$this->getData('name')] = $this->getMarketplaceCell($item[$this->getData('name')]);
            }
        }

        return $dataSource;
    }

    public function getMarketplaceCell(string $marketplace) {
        $content = "";
        if ($this->getMarketplaceLogo($marketplace)) {
            $content .= '<img src="' . $this->getMarketplaceLogo($marketplace) . '" alt="' . $marketplace . '" width="40" />';
        }
        $content .= '<span style="padding:5px;font-weight:bold">' . $marketplace . '</span>';

        return <<<HTML
            <div class="gubee-marketplace-logo" style="display: flex; align-items: center;">
                $content
            </div>
        HTML;
    }

    /**
     * Get marketplace logo
     */
    private function getMarketplaceLogo(string $marketplace): ?string {
        try {
            $platformConfig = $this->getPlatformConfig();
        } catch (Exception $e) {
            return null;
        }

        $logo = $platformConfig[$marketplace]['logoUrl'] ?? $platformConfig['HUBEE']['logoUrl'];
        return $logo ?: $platformConfig['HUBEE']['logoUrl'];
    }

    public function getPlatformConfig() {
        if (empty(self::$platform)) {
            $result = $this->platformResource->configuration();
            foreach ($result as $key => $value) {
                $result[$value['code']] = $value;
                unset($result[$key]);
            }

            self::$platform = $result;
        }

        return self::$platform;
    }
}
