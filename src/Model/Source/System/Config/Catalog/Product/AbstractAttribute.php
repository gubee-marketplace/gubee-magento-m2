<?php

declare(strict_types=1);

namespace Gubee\Integration\Model\Source\System\Config\Catalog\Product;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\Option\ArrayInterface;

use function array_column;
use function array_combine;

abstract class AbstractAttribute implements ArrayInterface
{
    protected Type $entityType;
    protected Attribute $attributeResource;

    public function __construct(
        Attribute $attributeResource,
        Type $entityType
    ) {
        $this->attributeResource = $attributeResource;
        $this->entityType        = $entityType;
    }

    /**
     * Retrieve All options for the attribute
     *
     * @return array<int, array<string, string>>
     */
    abstract public function toOptionArray();

    /**
     * Retrieve All options for the attribute
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return array_combine(
            array_column($this->toOptionArray(), 'value'),
            array_column($this->toOptionArray(), 'label')
        ) ?: [];
    }

    /**
     * Get the value of attributeResource
     */
    public function getAttributeResource(): Attribute
    {
        return $this->attributeResource;
    }

    /**
     * Get the value of entityType
     */
    public function getEntityType(): Type
    {
        return $this->entityType;
    }

    /**
     * Set the value of attributeResource
     *
     * @return self
     */
    public function setAttributeResource(Attribute $attributeResource)
    {
        $this->attributeResource = $attributeResource;

        return $this;
    }

    /**
     * Set the value of entityType
     *
     * @return self
     */
    public function setEntityType(Type $entityType)
    {
        $this->entityType = $entityType;

        return $this;
    }
}
