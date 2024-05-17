<?php

declare(strict_types=1);

namespace Gubee\Integration\Observer\Catalog\Product\Delete;

use Gubee\Integration\Command\Catalog\Product\DesativateCommand;
use Gubee\Integration\Observer\Catalog\Product\AbstractProduct;

class After extends AbstractProduct
{
    protected function process(): void
    {
        $this->queueManagement->append(
            DesativateCommand::class,
            [
                'sku' => $this->getProduct()->getSku(),
            ],
            (int) $this->getProduct()->getId()
        );
    }
}
