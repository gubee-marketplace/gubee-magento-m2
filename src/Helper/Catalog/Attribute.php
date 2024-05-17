<?php

declare(strict_types=1);

namespace Gubee\Integration\Helper\Catalog;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Model\Entity\Attribute\Frontend\AbstractFrontend;

use function array_filter;
use function array_map;
use function explode;

class Attribute
{
    /**
     * Get the raw attribute value for a given attribute code and product.
     *
     * @return mixed|null
     */
    public function getRawAttributeValue(string $attributeCode, ProductInterface $product)
    {
        $attribute = $this->getAttribute($attributeCode, $product);
        if (!$attribute) {
            return null;
        }

        $frontend = $attribute->getFrontend();
        if (!$frontend) {
            return null;
        }

        if ($attribute->getFrontendInput() === 'multiselect') {
            $value = $this->getMultiselectValue($attributeCode, $product);
            if (!$value) {
                return null;
            }

            return $this->getOptionLabels($value, $frontend);
        }
        return $product->getResource()
            ->getAttributeRawValue(
                $product->getId(),
                $attributeCode,
                $product->getStoreId()
            );
    }

    /**
     * Get the attribute value label for a given attribute code and product.
     *
     * @return mixed|null
     */
    public function getAttributeValueLabel(string $attributeCode, ProductInterface $product)
    {
        $attribute = $this->getAttribute($attributeCode, $product);
        if (!$attribute) {
            return null;
        }

        $frontend = $attribute->getFrontend();
        if (!$frontend) {
            return null;
        }

        if ($attribute->getFrontendInput() === 'multiselect') {
            $value = $this->getMultiselectValue($attributeCode, $product);
            if (!$value) {
                return null;
            }

            return $this->getOptionLabels($value, $frontend);
        }

        return $frontend->getValue($product);
    }

    /**
     * Get the attribute object for a given attribute code and product.
     *
     * @return \Magento\Eav\Model\Entity\Attribute|false
     */
    private function getAttribute(string $attributeCode, ProductInterface $product)
    {
        return $product->getResource()
            ->getAttribute($attributeCode);
    }

    /**
     * Get the multiselect value for a given attribute code and product.
     *
     * @return array|null
     */
    private function getMultiselectValue(string $attributeCode, ProductInterface $product)
    {
        $value = $product->getData($attributeCode);
        if (!$value) {
            return null;
        }

        $value = explode(',', $value ?: '');
        $value = array_filter($value);
        if (!$value) {
            return null;
        }

        return $value;
    }

    /**
     * Get the option labels for a given value array and frontend model.
     *
     * @param array $value
     * @return array
     */
    private function getOptionLabels(array $value, AbstractFrontend $frontend)
    {
        return array_map(
            function ($value) use ($frontend) {
                return $frontend->getOption($value);
            },
            $value
        );
    }

    public function isVariantAttribute(string $attributeCode, ProductInterface $product)
    {
        $productType = $product->getTypeId();
        if ($productType !== Configurable::TYPE_CODE) {
            return false;
        }

        $configurableAttributes = $product->getTypeInstance()->getConfigurableAttributes($product);
        foreach ($configurableAttributes as $configurableAttribute) {
            if ($configurableAttribute->getAttributeCode() === $attributeCode) {
                return true;
            }
        }

        return false;
    }
}
