<?php

declare(strict_types=1);

namespace Gubee\Integration\Api\Enum;

use Gubee\SDK\Enum\AbstractEnum;

class MainCategoryEnum extends AbstractEnum
{
    private const DEEPER  = 'DEEPER';
    private const SHALLOW = 'SHALLOW';

    /**
     * Returns a new instance of MainCategoryEnum with the value 'DEEPER'.
     */
    public static function DEEPER(): MainCategoryEnum
    {
        return new self(self::DEEPER);
    }

    /**
     * Returns a new instance of MainCategoryEnum with the value 'SHALLOW'.
     */
    public static function SHALLOW(): MainCategoryEnum
    {
        return new self(self::SHALLOW);
    }

    /**
     * Creates an instance of the enum class based on the given value.
     *
     * @param mixed $value The value to create the enum instance from.
     * @return self The enum instance.
     */
    public static function fromValue($value): self
    {
        return new self($value);
    }
}
