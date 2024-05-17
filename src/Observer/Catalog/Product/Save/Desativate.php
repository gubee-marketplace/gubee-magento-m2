<?php

declare(strict_types=1);

namespace Gubee\Integration\Observer\Catalog\Product\Save;

use Gubee\Integration\Command\Catalog\Product\DesativateCommand;
use Gubee\Integration\Observer\Catalog\Product\AbstractProduct;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

class Desativate extends AbstractProduct
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

    protected function isAllowed(): bool
    {
        if (! $this->getConfig()->getActive()) {
            return false;
        }
        $product = $this->getProduct();
        if (
            ! $this->attribute->getRawAttributeValue(
                'gubee',
                $product
            )
        ) {
            return false;
        }

        if (
            $product->getStatus() == Status::STATUS_ENABLED
        ) {
            return false;
        }

        return true;
    }
}
