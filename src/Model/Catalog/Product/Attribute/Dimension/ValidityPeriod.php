<?php

declare(strict_types=1);

namespace Gubee\Integration\Model\Catalog\Product\Attribute\Dimension;

use Gubee\SDK\Model\Catalog\Product\Attribute\Dimension\ValidityPeriod as SdkValidityPeriod;

class ValidityPeriod extends SdkValidityPeriod
{
    public function jsonSerialize(): array
    {
        return [
            'beginDt' => $this->getBeginDt()->format('Y-m-d'),
            'endDt'   => $this->getEndDt()->format('Y-m-d'),
        ];
    }
}
