<?php

declare(strict_types=1);

namespace Gubee\Integration\Model\Source\System\Config\Catalog\Product;

use Magento\Framework\Option\ArrayInterface;

use function strtolower;
use function ucfirst;

class MainCategory implements ArrayInterface
{
    /**
     * Retrieve All options
     *
     * @return array<int,array<string,string>>
     */
    public function toOptionArray()
    {
        $values = [
            'DEEPER',
            'SHALLOW',
        ];
        $result = [];
        foreach ($values as $value) {
            $result[] = [
                'label' => ucfirst(strtolower($value)),
                'value' => $value,
            ];
        }
        return $result;
    }
}
