<?php

declare(strict_types=1);

namespace Gubee\Integration\Observer\Catalog\Product\Price\Save;

use Gubee\Integration\Command\Catalog\Product\Price\SendCommand;
use Gubee\Integration\Observer\Catalog\Product\AbstractProduct;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;

class After extends AbstractProduct
{
    protected function process(): void
    {
        $parentIds = $this->objectManager->create(Configurable::class)
            ->getParentIdsByChild($this->getProduct()->getId());

        if (! empty($parentIds)) {
            foreach ($parentIds as $parentId) {
                $parentProduct = $this->productRepository->getById($parentId);
                if ($this->attribute->getRawAttributeValue('gubee', $parentProduct) != 1) {
                    continue;
                }
                $this->queueManagement->append(
                    SendCommand::class,
                    [
                        'sku' => $this->productRepository->getById($parentId)->getSku(),
                    ],
                    (int) $parentId
                );
            }
        }

        if ($this->attribute->getRawAttributeValue('gubee', $this->getProduct()) != 1) {
            return;
        }
        $this->queueManagement->append(
            SendCommand::class,
            [
                'sku' => $this->getProduct()->getSku(),
            ],
            (int) $this->getProduct()->getId()
        );
    }

    /**
     * Validate if the observer is allowed to run
     */
    protected function isAllowed(): bool
    {
        $product = $this->getProduct();
        if (
            ! $this->getProduct()
            ->dataHasChangedFor(
                $this->config->getPriceAttribute()
            )
        ) {
            return false;
        }

        return true;
    }
}
