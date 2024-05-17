<?php

declare(strict_types=1);

namespace Gubee\Integration\Model\Catalog\Product\Attribute\Source;

use Gubee\SDK\Enum\Catalog\Product\Attribute\Dimension\UnitTimeEnum;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Eav\Model\Entity\Attribute\Source\SourceInterface;
use ReflectionClass;

use function strtolower;
use function ucfirst;

class HandlingTime extends AbstractSource implements SourceInterface
{
    /**
     * Retrieve All options
     *
     * @return array<int,array<string,string>>
     */
    public function getAllOptions()
    {
        $options   = new ReflectionClass(UnitTimeEnum::class);
        $constants = $options->getConstants();
        $result    = [];
        foreach ($constants as $key => $value) {
            $result[] = [
                'label' => ucfirst(strtolower($value)),
                'value' => $key,
            ];
        }

        return $result;
    }

    /**
     * Retrieve Option value text
     *
     * @param string $value
     * @return string
     */
    public function getOptionText($value)
    {
        $options   = new ReflectionClass(UnitTimeEnum::class);
        $constants = $options->getConstants();
        return $constants[$value];
    }
}
