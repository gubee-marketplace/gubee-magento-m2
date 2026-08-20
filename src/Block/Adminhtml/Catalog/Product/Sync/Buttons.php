<?php

declare(strict_types=1);

namespace Gubee\Integration\Block\Adminhtml\Catalog\Product\Sync;

use Magento\Backend\Block\Widget\Button\Item;
use Magento\Backend\Block\Widget\Container;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;

class Buttons extends Container
{
    protected Registry $coreRegistry;

    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        $productId = $this->getProductId();

        $this->addButton(
            'gubee_sync_stock',
            [
                'label'      => __('Sync Stock'),
                'onclick'    => "setLocation('" . $this->getUrl('gubee/catalog_product_sync/stock', ['id' => $productId]) . "')",
                'sort_order' => 60,
                'class'      => 'secondary',
            ]
        );
        $this->addButton(
            'gubee_sync_price',
            [
                'label'      => __('Sync Pricing'),
                'onclick'    => "setLocation('" . $this->getUrl('gubee/catalog_product_sync/price', ['id' => $productId]) . "')",
                'sort_order' => 61,
                'class'      => 'secondary',
            ]
        );
        $this->addButton(
            'gubee_sync_product',
            [
                'label'      => __('Sync Whole Product'),
                'onclick'    => "setLocation('" . $this->getUrl('gubee/catalog_product_sync/product', ['id' => $productId]) . "')",
                'sort_order' => 62,
                'class'      => 'secondary',
            ]
        );
        parent::_construct();
    }

    public function canRender(Item $item)
    {
        return $this->isGubeeEnabled() && parent::canRender($item);
    }

    protected function isGubeeEnabled(): bool
    {
        $product = $this->coreRegistry->registry('current_product');
        return (bool) ($product && $product->getGubee());
    }

    protected function getProductId()
    {
        $product = $this->coreRegistry->registry('current_product');
        return $product ? $product->getId() : null;
    }
}
