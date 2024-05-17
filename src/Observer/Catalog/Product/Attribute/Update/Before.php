<?php

declare(strict_types=1);

namespace Gubee\Integration\Observer\Catalog\Product\Attribute\Update;

use Gubee\Integration\Command\Catalog\Product\SendCommand;
use Gubee\Integration\Model\Config;
use Gubee\Integration\Model\Queue\Management;
use Gubee\Integration\Observer\AbstractObserver;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;

class Before extends AbstractObserver
{
    public function __construct(
        Config $config,
        LoggerInterface $logger,
        Management $queueManagement,
        ProductRepositoryInterface $productRepository
    ) {
        parent::__construct($config, $logger, $queueManagement);
        $this->productRepository = $productRepository;
    }

    protected function process(): void
    {
        foreach ($this->getObserver()->getProductIds() as $productId) {
            $product = $this->productRepository->getById($productId);
            $this->queueManagement->append(
                SendCommand::class,
                ['sku' => $product->getSku()],
                (int) $product->getId()
            );
        }
    }

    protected function isAllowed(): bool
    {
        $attributeData = $this->getObserver()->getAttributesData();
        return isset($attributeData['gubee'])
        && $attributeData['gubee'] === 1
        && parent::isAllowed();
    }
}
