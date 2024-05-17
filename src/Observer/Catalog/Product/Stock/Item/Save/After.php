<?php

declare(strict_types=1);

namespace Gubee\Integration\Observer\Catalog\Product\Stock\Item\Save;

use Gubee\Integration\Command\Catalog\Product\Stock\SendCommand;
use Gubee\Integration\Observer\Catalog\Product\AbstractProduct;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Event\Observer;

use function json_decode;
use function json_encode;

class After extends AbstractProduct
{
    protected function process(): void
    {
        $parents = $this->objectManager->create(
            Configurable::class
        )->getParentIdsByChild(
            $this->getProduct()->getId()
        );

        if (! empty($parents)) {

            foreach ($parents as $parentId) {
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
        $this->appendForParent(
            $this->getProduct()
        );
    }

    /**
     * Execute the observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (!$observer->getDataObject() instanceof ProductInterface) {
            $product = $this->productRepository->getById(
                $observer->getDataObject()->getProductId()
            );
        } else {
            $product = $observer->getDataObject();
        }
        $this->setProduct($product);

        if ($this->isAllowed()) {
            $this->process();
        }
    }

    /**
     * Validate if the observer is allowed to run
     */
    protected function isAllowed(): bool
    {
        if (! $this->getConfig()->getActive()) {
            return false;
        }
        $product      = $this->getProduct();
        $productStock = $this->objectManager->get(
            StockRegistryInterface::class
        )->getStockItem(
            $product->getId(),
            $product->getStore()->getWebsiteId()
        );

        $origJson = json_decode(
            json_encode(
                $product->getOrigData()
            ),
            true
        );
        if (
            isset($origJson['quantity_and_stock_status']) &&
            isset($origJson['quantity_and_stock_status']['qty']) &&
            isset($origJson['quantity_and_stock_status']['is_in_stock'])
        ) {
            if (
                $origJson['quantity_and_stock_status']['qty']
                ==
                $productStock->getData('qty')
                &&
                $origJson['quantity_and_stock_status']['is_in_stock']
                ==
                $productStock->getData('is_in_stock')
            ) {
                return false;
            }
        }
        return true;
    }
}
