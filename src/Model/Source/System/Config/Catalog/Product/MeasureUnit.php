<?php

declare(strict_types=1);

namespace Gubee\Integration\Model\Source\System\Config\Catalog\Product;

use Gubee\SDK\Enum\Catalog\Product\Attribute\Dimension\Measure\TypeEnum;
use Magento\Framework\Option\ArrayInterface;
use ReflectionClass;

class MeasureUnit implements ArrayInterface
{
    /**
     * Retrieve All options
     *
     * @return array<int,array<string,string>>
     */
    public function toOptionArray()
    {
        $typeEnum  = new ReflectionClass(TypeEnum::class);
        $constants = $typeEnum->getConstants();
        $result    = [];
        foreach ($constants as $key => $value) {
            $result[] = [
                'label' => $value,
                'value' => $key,
            ];
        }

        return $result;
    }
}
