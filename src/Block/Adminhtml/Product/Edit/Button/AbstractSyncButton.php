<?php

declare(strict_types=1);

namespace Gubee\Integration\Block\Adminhtml\Product\Edit\Button;

use Gubee\Integration\Helper\Catalog\Attribute;
use Magento\Catalog\Block\Adminhtml\Product\Edit\Button\Generic;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponent\Context;

use function __;

abstract class AbstractSyncButton extends Generic
{
    protected Attribute $attribute;

    public function __construct(
        Context $context,
        Registry $registry,
        Attribute $attribute
    ) {
        parent::__construct($context, $registry);
        $this->attribute = $attribute;
    }

    abstract protected function getLabel(): string;

    abstract protected function getRoute(): string;

    abstract protected function getSortOrder(): int;

    /**
     * @return array<string, mixed>
     */
    public function getButtonData(): array
    {
        $product   = $this->getProduct();
        $productId = (int) $product->getId();
        if (! $productId || ! $this->attribute->getRawAttributeValue('gubee', $product)) {
            return [];
        }

        return [
            'label'      => __($this->getLabel()),
            'on_click'   => "setLocation('" . $this->getUrl($this->getRoute(), ['id' => $productId]) . "')",
            'sort_order' => $this->getSortOrder(),
            'class'      => 'secondary',
        ];
    }
}
