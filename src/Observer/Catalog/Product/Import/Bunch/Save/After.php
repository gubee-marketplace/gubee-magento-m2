<?php

declare(strict_types=1);

namespace Gubee\Integration\Observer\Catalog\Product\Import\Bunch\Save;

use Gubee\Integration\Command\Catalog\Product\Price\SendCommand;
use Gubee\Integration\Helper\Catalog\Attribute;
use Gubee\Integration\Model\Config;
use Gubee\Integration\Model\Queue\Management;
use Gubee\Integration\Observer\AbstractObserver;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Psr\Log\LoggerInterface;

class After extends AbstractObserver
{
    protected ProductRepositoryInterface $productRepository;
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
        $priceAttribute = $this->config->getPriceAttribute();
        $bunch          = (array) $this->getObserver()->getEvent()->getBunch();

        foreach ($bunch as $row) {
            if (empty($row['sku']) || ! array_key_exists($priceAttribute, $row) || $row[$priceAttribute] === '') {
                continue;
            }

            try {
                $product = $this->productRepository->get((string) $row['sku'], false, null, true);
            } catch (NoSuchEntityException $e) {
                continue;
            }

            if ($this->attribute->getRawAttributeValue('gubee', $product) != 1) {
                continue;
            }

            $this->sendPrice($product);
        }
    }

    /**
     * Queue the price for the given product and, if it is a variation, its configurable parents.
     */
    protected function sendPrice(ProductInterface $product): void
    {
        $this->queueManagement->append(
            SendCommand::class,
            ['sku' => $product->getSku()],
            (int) $product->getId()
        );

        $parentIds = $this->objectManager->create(Configurable::class)
            ->getParentIdsByChild($product->getId());
        foreach ($parentIds as $parentId) {
            $parentProduct = $this->productRepository->getById($parentId);
            if ($this->attribute->getRawAttributeValue('gubee', $parentProduct) == 1) {
                $this->queueManagement->append(
                    SendCommand::class,
                    ['sku' => $parentProduct->getSku()],
                    (int) $parentId
                );
            }
        }
    }
}
