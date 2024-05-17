<?php

declare(strict_types=1);

namespace Gubee\Integration\Model\Source\System\Config\Catalog\Product;

use Magento\Catalog\Model\Product;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

use function __;
use function strcmp;
use function usort;

class Attribute extends AbstractAttribute
{
    /**
     * Retrieve All options for the attribute type
     *
     * @return array<int, array<int|string, string>>
     */
    public function toOptionArray()
    {
        $options = [
            [
                'value' => '',
                'label' => __('-- Please Select --'),
            ],
        ];
        foreach ($this->getCollection() as $attribute) {
            $options[] = [
                'value' => $attribute->getAttributeCode(),
                'label' => $attribute->getFrontendLabel(),
            ];
        }
        $options[] = [
            'value' => 'entity_id',
            'label' => __("Product ID"),
        ];
        usort($options, function ($a, $b) {
            return strcmp((string) $a['label'], (string) $b['label']);
        });
        return $options;
    }

    protected function getCollection(): AbstractCollection
    {
        $catalogAttributeSetId = $this->getEntityType()
            ->loadByCode(
                Product::ENTITY
            )
            ->getId();
        return $this->getAttributeResource()
            ->getCollection()
            ->addFieldToFilter(
                'entity_type_id',
                $catalogAttributeSetId
            );
    }
}
