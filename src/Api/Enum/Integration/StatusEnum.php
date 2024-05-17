<?php

declare(strict_types=1);

namespace Gubee\Integration\Api\Enum\Integration;

use Gubee\SDK\Enum\AbstractEnum;

class StatusEnum extends AbstractEnum
{
    private const INTEGRATED     = "1";
    private const NOT_INTEGRATED = "0";

    public static function INTEGRATED(): self
    {
        return new self(self::INTEGRATED);
    }

    public static function NOT_INTEGRATED(): self
    {
        return new self(self::NOT_INTEGRATED);
    }

    /**
     * Create a new instance of the enum based into a given value
     *
     * @param mixed $value
     */
    public static function fromValue($value): self
    {
        return new self($value);
    }
}
