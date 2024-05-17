<?php

declare(strict_types=1);

namespace Gubee\Integration\Model\Source\System\Config\Log;

use Magento\Framework\Data\OptionSourceInterface;
use Psr\Log\LogLevel;
use ReflectionClass;

use function strtolower;
use function ucfirst;

class Level implements OptionSourceInterface
{
    /**
     * Retrieve All options
     *
     * @return array<int,array<string,string>>
     */
    public function toOptionArray()
    {
        $reflection = new ReflectionClass(LogLevel::class);
        $constants  = $reflection->getConstants();
        $result     = [];
        foreach ($constants as $key => $value) {
            $result[] = [
                'label' => ucfirst(strtolower($value)),
                'value' => $key,
            ];
        }

        return $result;
    }
}
