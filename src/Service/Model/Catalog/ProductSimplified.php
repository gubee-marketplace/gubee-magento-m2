<?php

declare(strict_types=1);

namespace Gubee\Integration\Service\Model\Catalog;

use Gubee\Integration\Helper\Catalog\Attribute;
use Gubee\Integration\Model\Catalog\ProductV2;
use Gubee\Integration\Model\Catalog\ProductV2\Variation;
use Gubee\Integration\Model\Config;
use Gubee\SDK\Api\ServiceProviderInterface;
use Gubee\SDK\Enum\Catalog\Product\Attribute\Dimension\UnitTime\TypeEnum as UnitTimeTypeEnum;
use Gubee\SDK\Enum\Catalog\Product\Attribute\OriginEnum;
use Gubee\SDK\Enum\Catalog\Product\StatusEnum;
use Gubee\SDK\Enum\Catalog\Product\TypeEnum;
use Gubee\SDK\Enum\Catalog\Product\Variation\ConditionEnum;
use Gubee\SDK\Enum\Catalog\Product\Variation\Price\TypeEnum as PriceTypeEnum;
use Gubee\SDK\Model\Catalog\Product\Variation\Stock;
use Gubee\SDK\Resource\Catalog\Product\Variation\PriceResource;
use Gubee\SDK\Resource\Catalog\Product\Variation\StockResource;
use Gubee\SDK\Resource\Catalog\ProductResource;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;

use function array_filter;
use function count;
use function preg_replace;

class ProductSimplified
{
    public string $sku;
    public string $name;
    public ?string $description = null;
    public ?string $brand       = null;
    public ?string $category    = null;
    public ?float $price        = null;
    public ?float $weight       = null;
    public ?int $quantity       = null;
    public ?string $imageUrl    = null;

    public ?ProductV2 $gubeeProduct = null;

    protected Config $config;
    protected Attribute $attribute;
    protected ServiceProviderInterface $serviceProvider;
    protected ProductResource $productResource;
    protected StockResource $stockResource;
    protected PriceResource $priceResource;
    protected CollectionFactory $categoryCollectionFactory;
    protected StockRegistryInterface $stockRegistry;
    protected string $sellerId;
    protected ProductInterface $product;

    public function __construct(
        Config $config,
        Attribute $attribute,
        ServiceProviderInterface $serviceProvider,
        ProductResource $productResource,
        StockResource $stockResource,
        PriceResource $priceResource,
        CollectionFactory $categoryCollectionFactory,
        StockRegistryInterface $stockRegistry,
        string $sellerId,
        ProductInterface $product
    ) {
        $this->config                    = $config;
        $this->attribute                 = $attribute;
        $this->serviceProvider           = $serviceProvider;
        $this->productResource           = $productResource;
        $this->stockResource             = $stockResource;
        $this->priceResource             = $priceResource;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->stockRegistry             = $stockRegistry;
        $this->sellerId                  = $sellerId;
        $this->product                   = $product;

        $this->hydrate();
    }

    protected function hydrate(): void
    {
        $product = $this->product;

        $this->sku         = $product->getSku();
        $this->name        = $product->getName();
        $this->description = $product->getDescription();
        $this->brand       = $this->attribute->getAttributeValueLabel(
            $this->config->getBrandAttribute(),
            $product
        );
        $this->category    = $this->buildCategoryName();
        $this->price       = (float) $product->getPrice();
        $this->weight      = (float) $product->getWeight();
        $this->quantity    = (int) $this->stockRegistry->getStockItem(
            $product->getId()
        )->getQty();
        $this->imageUrl    = $this->buildImageUrl();
    }

    public function save(): ProductV2
    {
        return $this->productResource->createOrUpdate(
            $this->getGubeeProduct()
        );
    }

    public function desativate(): void
    {
        $this->getGubeeProduct()->setStatus(
            StatusEnum::INACTIVE()
        );
        $this->save();
    }

    public function getGubeeProduct(): ProductV2
    {
        if ($this->gubeeProduct === null) {
            $this->gubeeProduct = $this->buildGubeeProduct();
        }
        return $this->gubeeProduct;
    }

    protected function buildGubeeProduct(): ProductV2
    {
        return $this->serviceProvider->create(
            ProductV2::class,
            [
                'sellerId'         => $this->sellerId,
                'mainSku'          => $this->sku,
                'name'             => $this->name,
                'mainCategory'     => $this->category ?? 'Default',
                'brand'            => $this->brand ?? '',
                'type'             => TypeEnum::SIMPLE(),
                'origin'           => OriginEnum::NATIONAL(),
                'status'           => StatusEnum::ACTIVE(),
                'accounts'         => [],
                'specifications'   => [],
                'variations'       => [$this->buildVariation()],
                'addNewVariations' => true,
                'downloadImages'   => true,
            ]
        );
    }

    protected function buildVariation(): Variation
    {
        return $this->serviceProvider->create(
            Variation::class,
            array_filter(
                [
                    'sku'                  => $this->sku,
                    'main'                 => true,
                    'name'                 => $this->name,
                    'condition'            => ConditionEnum::NEW(),
                    'status'               => StatusEnum::ACTIVE(),
                    'warrantyTime'         => (string) $this->config->getDefaultDeliveryTime(),
                    'cost'                 => [
                        'currency' => 'BRL',
                        'amount'   => (float) ($this->price ?? 0),
                    ],
                    'dimension'            => $this->buildDimension(),
                    'prices'               => [$this->buildPrice()],
                    'stocks'               => [$this->buildStock()],
                    'images'               => $this->buildImages(),
                    'variantSpecification' => [],
                    'description'          => $this->description,
                ],
                fn ($value) => $value !== null
            )
        );
    }

    protected function buildDimension(): array
    {
        return [
            'height' => [
                'type'  => $this->config->getMeasureUnitAttribute(),
                'value' => 0.0,
            ],
            'width'  => [
                'type'  => $this->config->getMeasureUnitAttribute(),
                'value' => 0.0,
            ],
            'depth'  => [
                'type'  => $this->config->getMeasureUnitAttribute(),
                'value' => 0.0,
            ],
            'weight' => [
                'type'  => $this->config->getWeightUnit(),
                'value' => (float) ($this->weight ?? 0),
            ],
        ];
    }

    protected function buildPrice(): array
    {
        return [
            'type'  => PriceTypeEnum::DEFAULT(),
            'value' => [
                'currency' => 'BRL',
                'amount'   => (float) ($this->price ?? 0),
            ],
        ];
    }

    protected function buildStock(): array
    {
        return [
            'warehouseId' => 'default-warehouse',
            'qty'         => $this->quantity ?? 0,
            'priority'    => 1,
        ];
    }

    protected function buildImages(): array
    {
        if ($this->imageUrl === null) {
            return [];
        }
        return [
            [
                'url'   => $this->imageUrl,
                'order' => 0,
                'name'  => 'Image',
                'main'  => true,
            ],
        ];
    }

    protected function buildCategoryName(): ?string
    {
        $categories = $this->product->getCategoryIds();
        if (empty($categories)) {
            return null;
        }
        $collection = $this->categoryCollectionFactory->create()
            ->addAttributeToFilter('entity_id', ['in' => $categories])
            ->addAttributeToSelect('name')
            ->setPageSize(1)
            ->setOrder('level', 'DESC');
        $category   = $collection->getFirstItem();
        return $category->getId() ? $category->getName() : null;
    }

    protected function buildImageUrl(): ?string
    {
        $images = $this->product->getMediaGalleryImages();
        if ($images === null || count($images) === 0) {
            return null;
        }
        $first = $images->getFirstItem();
        $url   = $first->getUrl();
        if ($url === null || $url === '') {
            return null;
        }
        return preg_replace('/^https?:/', '', $url);
    }

    public function saveStock()
    {
        $stock = $this->serviceProvider->create(
            Stock::class,
            [
                'crossDockingTime' => [
                    'type'  => UnitTimeTypeEnum::DAYS(),
                    'value' => 0,
                ],
                'priority'         => 1,
                'qty'              => $this->quantity ?? 0,
                'warehouseId'      => 'default-warehouse',
                'sku'              => $this->sku,
            ]
        );
        $this->stockResource->updateStockBySku($stock);
    }

    public function savePrice()
    {
        $this->priceResource->updatePricesBySku(
            $this->sku,
            [
                [
                    'type'  => PriceTypeEnum::DEFAULT()->jsonSerialize(),
                    'value' => [
                        'currency' => 'BRL',
                        'amount'   => (float) ($this->price ?? 0),
                    ],
                ],
            ]
        );
    }
}
