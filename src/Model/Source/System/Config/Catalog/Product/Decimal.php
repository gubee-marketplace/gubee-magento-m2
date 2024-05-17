<?php

declare(strict_types=1);

namespace Gubee\Integration\Model\Source\System\Config\Catalog\Product;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Decimal extends Attribute
{
    protected function getCollection(): AbstractCollection
    {
        return parent::getCollection()->addFieldToFilter(
            'backend_type',
            'decimal'
        );
    }
}
