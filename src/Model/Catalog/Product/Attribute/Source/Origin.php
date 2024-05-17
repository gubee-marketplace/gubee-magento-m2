<?php

declare(strict_types=1);

namespace Gubee\Integration\Model\Catalog\Product\Attribute\Source;

use Gubee\SDK\Enum\Catalog\Product\Attribute\OriginEnum;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Eav\Model\Entity\Attribute\Source\SourceInterface;
use ReflectionClass;

use function explode;
use function implode;
use function strtolower;
use function ucfirst;

class Origin extends AbstractSource implements SourceInterface
{
    /**
     * Retrieve All options
     *
     * @return array<int,array<string,string>>
     */
    public function getAllOptions()
    {
        $options   = new ReflectionClass(OriginEnum::class);
        $constants = $options->getConstants();
        $result    = [];
        foreach ($constants as $key => $value) {
            $result[] = [
                'label' => $this->fixLabel($value),
                'value' => $key,
            ];
        }

        return $result;
    }

    private function fixLabel(string $label): string
    {
        return ucfirst(
            strtolower(
                implode(
                    ' ',
                    explode('_', $label)
                )
            )
        );
    }

    /**
     * Retrieve Option value text
     *
     * @param string $value
     * @return mixed
     */
    public function getOptionText($value)
    {
        $options   = new ReflectionClass(OriginEnum::class);
        $constants = $options->getConstants();
        if (isset($constants[$value])) {
            return $this->fixLabel($constants[$value]);
        }
        return false;
    }
}
