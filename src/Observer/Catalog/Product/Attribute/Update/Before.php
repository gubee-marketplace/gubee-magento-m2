<?php

declare(strict_types=1);

namespace Gubee\Integration\Observer\Catalog\Product\Attribute\Update;

use Gubee\Integration\Command\Catalog\Product\SendCommand;
use Gubee\Integration\Command\Catalog\Product\Price\SendCommand as PriceSendCommand;
use Gubee\Integration\Helper\Catalog\Attribute;
use Gubee\Integration\Model\Config;
use Gubee\Integration\Model\Queue\Management;
use Gubee\Integration\Observer\AbstractObserver;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Framework\ObjectManagerInterface;
use Psr\Log\LoggerInterface;

class Before extends AbstractObserver
{
    public $productRepository;
    protected Attribute $attribute;
    protected ObjectManagerInterface $objectManager;

    public function __construct(
        Config $config,
        LoggerInterface $logger,
        Management $queueManagement,
        ProductRepositoryInterface $productRepository,
        Attribute $attribute,
        ObjectManagerInterface $objectManager
    ) {
        parent::__construct($config, $logger, $queueManagement);
        $this->productRepository = $productRepository;
        $this->attribute         = $attribute;
        $this->objectManager     = $objectManager;
    }

    protected function process(): void
    {
        $attributeData = $this->getObserver()->getAttributesData();
        $isGubeeEnable = isset($attributeData['gubee']) && $attributeData['gubee'] === 1;
        $isPriceChange = array_key_exists($this->config->getPriceAttribute(), $attributeData);

        foreach ($this->getObserver()->getProductIds() as $productId) {
            $product = $this->productRepository->getById($productId);

            if ($isGubeeEnable) {
                $this->queueManagement->append(
                    SendCommand::class,
                    ['sku' => $product->getSku()],
                    (int) $productId
                );
            }

            if ($isPriceChange && $this->attribute->getRawAttributeValue('gubee', $product) == 1) {
                $this->sendPrice($product);
            }
        }
    }

    /**
     * Queue the price for the given product and, if it is a variation, its configurable parents.
     */
    protected function sendPrice(ProductInterface $product): void
    {
        $this->queueManagement->append(
            PriceSendCommand::class,
            ['sku' => $product->getSku()],
            (int) $product->getId()
        );

        $parentIds = $this->objectManager->create(Configurable::class)
            ->getParentIdsByChild($product->getId());
        foreach ($parentIds as $parentId) {
            $parentProduct = $this->productRepository->getById($parentId);
            if ($this->attribute->getRawAttributeValue('gubee', $parentProduct) == 1) {
                $this->queueManagement->append(
                    PriceSendCommand::class,
                    ['sku' => $parentProduct->getSku()],
                    (int) $parentId
                );
            }
        }
    }

    protected function isAllowed(): bool
    {
        $attributeData = $this->getObserver()->getAttributesData();
        $isGubeeEnable = isset($attributeData['gubee']) && $attributeData['gubee'] === 1;
        $isPriceChange = array_key_exists($this->config->getPriceAttribute(), $attributeData);

        return ($isGubeeEnable || $isPriceChange) && parent::isAllowed();
    }
}
