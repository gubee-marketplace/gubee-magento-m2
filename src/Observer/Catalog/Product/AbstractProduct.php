<?php

declare(strict_types=1);

namespace Gubee\Integration\Observer\Catalog\Product;

use Gubee\Integration\Helper\Catalog\Attribute;
use Gubee\Integration\Model\Config;
use Gubee\Integration\Model\Queue\Management;
use Gubee\Integration\Observer\AbstractObserver;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Framework\Event\Observer;
use Magento\Framework\ObjectManagerInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractProduct extends AbstractObserver
{
    protected Attribute $attribute;
    /** @var ProductInterface|null */
    protected $product;
    protected ProductRepositoryInterface $productRepository;
    protected ObjectManagerInterface $objectManager;

    public function __construct(
        Config $config,
        LoggerInterface $logger,
        Management $queueManagement,
        Attribute $attribute,
        ProductRepositoryInterface $productRepository,
        ObjectManagerInterface $objectManager
    ) {
        parent::__construct($config, $logger, $queueManagement);
        $this->attribute         = $attribute;
        $this->productRepository = $productRepository;
        $this->objectManager     = $objectManager;
    }

    public function appendForParent(
        ProductInterface $product
    ) {
        $parentIds = $this->objectManager->create(Configurable::class)
            ->getParentIdsByChild($product->getId());
        if (empty($parentIds)) {
            return $this;
        }
        foreach ($parentIds as $parentId) {
            $parent = $this->productRepository->getById($parentId);
            if (
                ! $this->attribute->getRawAttributeValue(
                    'gubee',
                    $parent
                )
            ) {
                continue;
            }
            if (
                ! $this->attribute->getRawAttributeValue(
                    'gubee_sync',
                    $parent
                )
            ) {
                continue;
            }

            $this->setProduct($parent);
            $this->process();
        }
    }

    /**
     * Execute the observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        $this->setObserver($observer);
        if ($this->getObserver()->getEvent()->getDataObject()) {
            $this->setProduct(
                $this->getObserver()->getEvent()->getDataObject()
            );
        }
        if ($this->isAllowed()) {
            $this->process();
            $this->appendForParent(
                $this->getProduct()
            );
        }
    }

    /**
     * Validate if the observer is allowed to run
     */
    protected function isAllowed(): bool
    {
        if (!$product = $this->getProduct()) {
            return false;
        }

        if (
            ! $this->attribute->getRawAttributeValue(
                'gubee_sync',
                $product
            )
            &&
            ! $product->dataHasChangedFor('gubee')
        ) {
            return false;
        }

        if (
            ! $this->attribute->getRawAttributeValue(
                'gubee',
                $product
            )
        ) {
            return false;
        }

        return parent::isAllowed();
    }

    /**
     * @return ProductInterface|null
     */
    public function getProduct()
    {
        return $this->product;
    }

    public function setProduct(ProductInterface $product): self
    {
        $this->product = $product;
        return $this;
    }
}
