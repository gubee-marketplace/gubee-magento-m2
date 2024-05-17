<?php

declare(strict_types=1);

namespace Gubee\Integration\Service\Model\Catalog\Product;

use Gubee\SDK\Enum\Catalog\Product\Attribute\TypeEnum;
use Gubee\SDK\Model\Catalog\Product\Attribute as ProductAttribute;
use Gubee\SDK\Resource\Catalog\Product\AttributeResource;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\ObjectManagerInterface;

use function in_array;
use function trim;

class Attribute
{
    protected AttributeResource $attributeResource;
    protected ProductAttribute $gubeeAttribute;
    protected ProductAttributeInterface $attribute;

    /**
     * @param array<string>|null $options
     */
    public function __construct(
        ProductAttributeInterface $attribute,
        AttributeResource $attributeResource,
        ObjectManagerInterface $objectManager
    ) {
        $this->attribute         = $attribute;
        $this->attributeResource = $attributeResource;
        $this->gubeeAttribute    = $objectManager->create(
            ProductAttribute::class,
            [
                'name'     => $attribute->getAttributeCode(),
                'id'       => $attribute->getAttributeCode(),
                'label'    => $attribute->getDefaultFrontendLabel(),
                'required' => $attribute->getIsRequired() ?: false,
            ]
        );
        switch ($attribute->getFrontendInput()) {
            case 'textarea':
                $this->gubeeAttribute->setAttrType(TypeEnum::TEXTAREA());
                break;
            case 'multiselect':
                $this->gubeeAttribute->setAttrType(TypeEnum::MULTISELECT());
                break;
            case 'select':
                $this->gubeeAttribute->setAttrType(TypeEnum::SELECT());
                break;
            case 'text':
            case 'price':
            default:
                $this->gubeeAttribute->setAttrType(TypeEnum::TEXT());
                break;
        }

        $isVariant = false;
        if (
            $attribute->getIsGlobal() === "1"/** @phpstan-ignore-line */
            && $attribute->getIsUserDefined() === "1"/** @phpstan-ignore-line */
            && $attribute->getFrontendInput() === "select"
        ) {
            if (
                in_array(
                    Configurable::TYPE_CODE,
                    $attribute->getApplyTo()
                )
            ) {
                $isVariant = true;
            }
            if (! $attribute->getApplyTo() || empty($attribute->getApplyTo())) {
                $isVariant = true;
            }
        }

        $this->gubeeAttribute->setVariant($isVariant);
        $options = [];
        foreach ($attribute->getOptions() ?: [] as $option) {
            $label = (string) $option->getLabel();
            $label = trim($label);
            if ($label === '') {
                continue;
            }
            $options[] = $label;
        }

        $this->gubeeAttribute->setOptions($options);
    }

    public function getGubeeAttribute(): ProductAttribute
    {
        return $this->gubeeAttribute;
    }

    public function setGubeeAttribute(ProductAttribute $gubeeAttribute): self
    {
        $this->gubeeAttribute = $gubeeAttribute;
        return $this;
    }
}
