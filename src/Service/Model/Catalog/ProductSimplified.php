<?php

declare(strict_types=1);

namespace Gubee\Integration\Service\Model\Catalog;

use Gubee\Integration\Api\Enum\MainCategoryEnum;
use Gubee\Integration\Helper\Catalog\Attribute;
use Gubee\SDK\Model\Catalog\ProductV2;
use Gubee\SDK\Model\Catalog\ProductV2\Variation;
use Gubee\Integration\Model\Config;
use Gubee\Integration\Service\Model\Catalog\ProductSimplified\VariationFactory;
use Gubee\Integration\Model\ResourceModel\Catalog\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Gubee\Integration\Service\Model\Catalog\ProductSimplified\Variation as ProductVariation;
use Gubee\SDK\Api\ServiceProviderInterface;
use Gubee\SDK\Enum\Catalog\Product\Attribute\Dimension\UnitTime\TypeEnum as UnitTimeTypeEnum;
use Gubee\SDK\Enum\Catalog\Product\Attribute\OriginEnum;
use Gubee\SDK\Enum\Catalog\Product\StatusEnum;
use Gubee\SDK\Enum\Catalog\Product\TypeEnum;
use Gubee\SDK\Enum\Catalog\Product\Variation\Price\TypeEnum as PriceTypeEnum;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Gubee\SDK\Model\Catalog\Product\Variation\Stock;
use Gubee\SDK\Model\Catalog\ProductV2\Specification;
use Gubee\SDK\Resource\Catalog\Product\Variation\PriceResource;
use Gubee\SDK\Resource\Catalog\Product\Variation\StockResource;
use Gubee\SDK\Resource\Catalog\ProductResource;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Store\Model\StoreManagerInterface;

class ProductSimplified
{
    public string $sku;
    public ?string $description = null;
    public ?string $brand       = null;
    public ?string $category    = null;
    public ?float $price        = null;
    public ?int $quantity       = null;
    public ?array $specifications = null;
    public ?array $variations = null;

    public ?ProductV2 $gubeeProduct = null;

    protected Config $config;
    protected Attribute $attribute;
    protected ServiceProviderInterface $serviceProvider;
    protected ProductResource $productResource;
    protected StockResource $stockResource;
    protected PriceResource $priceResource;
    protected CollectionFactory $categoryCollectionFactory;
    protected StoreManagerInterface $storeManager;
    protected StockRegistryInterface $stockRegistry;
    protected VariationFactory $variationFactory;
    protected string $sellerId;
    protected ProductInterface $product;

    protected \Magento\Framework\ObjectManagerInterface $objectManager;

    protected $attributeCollection;

    public function __construct(
        Config $config,
        Attribute $attribute,
        ServiceProviderInterface $serviceProvider,
        ProductResource $productResource,
        StockResource $stockResource,
        PriceResource $priceResource,
        CollectionFactory $categoryCollectionFactory,
        StoreManagerInterface $storeManager,
        StockRegistryInterface $stockRegistry,
        VariationFactory $variationFactory,
        AttributeCollectionFactory $attributeCollectionFactory,
        ProductInterface $product
    ) {
        $this->config                    = $config;
        $this->attribute                 = $attribute;
        $this->serviceProvider           = $serviceProvider;
        $this->productResource           = $productResource;
        $this->stockResource             = $stockResource;
        $this->priceResource             = $priceResource;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->storeManager              = $storeManager;
        $this->stockRegistry             = $stockRegistry;
        $this->variationFactory          = $variationFactory;
        $this->product                   = $product;
        $this->attributeCollection       = $attributeCollectionFactory->create();
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->buildGubeeProduct();
    }
    public function save()
    {
        return $this->productResource->createOrUpdate(
            $this->getGubeeProduct()
        );
    }

    public function deactivate()
    {
        $this->productResource->patchStatus(
            $this->product->getSku(),
            StatusEnum::INACTIVE()->jsonSerialize()
        );
    }

    public function activate()
    {
        $this->productResource->patchStatus(
            $this->product->getSku(),
            StatusEnum::ACTIVE()->jsonSerialize()
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
                'sellerId'         => '',
                'mainSku'          => $this->product->getSku(),
                'name'             => $this->product->getName(),
                'mainCategory'     => $this->buildMainCategory(),
                'brand'            => $this->buildBrand() ?? '',
                'type'             => $this->buildType(),
                'origin'           => OriginEnum::NATIONAL(),
                'status'           => $this->buildStatus(),
                'accounts'         => [],
                'specifications'   => $this->buildSpecifications(),
                'variations'       => $this->buildVariations(),
                'addNewVariations' => true,
                'downloadImages'   => true,
            ]
        );
    }

    private function buildStatus()
    {
        return $this->product->getStatus() == Status::STATUS_DISABLED ?
            StatusEnum::INACTIVE() :
            StatusEnum::ACTIVE();
    }

    private function buildType()
    {
        if ($this->product->getTypeId() == Configurable::TYPE_CODE) {
            return TypeEnum::VARIANT();
        }
        $parents = $this->product
            ->getTypeInstance()
            ->getParentIdsByChild(
                $this->product->getId()
            );
        if (count($parents) > 0) {
            return TypeEnum::VARIANT();
        }
        return TypeEnum::SIMPLE();
    }

    private function buildBrand()
    {
        $brand = $this->attribute->getAttributeValueLabel(
            $this->config->getBrandAttribute(),
            $this->product
        );

        if (! $brand) {
            return null;
        }

        return (string) $brand;
    }

    /**
     * @return Variation[]
     */
    private function buildVariations()
    {
        if ($this->variations) {
            return $this->variations;
        }
        if ($this->product->getTypeId() != Configurable::TYPE_CODE) {
            $variation = $this->objectManager->create(
                ProductVariation::class,
                [
                    'product' => $this->product,
                ]
            )->getVariation();

            // remove variantSpecification from simple products
            $variation->setVariantSpecification([]);

            $variation->setMain(true);
            $this->variations = [
                $variation,
            ];
            return $this->variations;
        }

        $variations = [];
        $main       = true;
        $children   = $this->product
            ->getTypeInstance()
            ->getUsedProducts($this->product);
        foreach ($children as $child) {
            $variation = $this->objectManager->create(
                ProductVariation::class,
                [
                    'product' => $child,
                    'parent'  => $this->product,
                ]
            )->getVariation();
            $variation->setMain($main);
            $variations[] = $variation;
            $main         = false;
        }

        $this->variations = $variations;
        return $variations;
    }

    private function buildMainCategory()
    {
        $categories = $this->product->getCategoryIds();
        $collection = $this->categoryCollectionFactory->create()
            ->addAttributeToFilter('entity_id', ['in' => $categories])
            ->addAttributeToSelect('name');
        $collection->getSelect()->limit(1);
        $collection->getSelect()->order(
            'level',
            $this->config->getMainCategoryPosition()
            ==
            MainCategoryEnum::DEEPER()
            ? 'DESC'
            : 'ASC'
        );
        $category = $collection->getFirstItem();
        if (! $category->getId()) {
            // fall back to the current store's own root category
            $rootCategoryId = $this->storeManager->getStore()->getRootCategoryId();
            $category       = $this->categoryCollectionFactory->create()
                ->addAttributeToFilter('entity_id', $rootCategoryId)
                ->addAttributeToSelect('name')
                ->getFirstItem();
        }

        $hierarchy = $this->buildCategoryHierarchy($category);

        return $hierarchy ?: (string) $category->getName();
    }

    private function buildCategoryHierarchy(Category $category): ?string
    {
        if (! $category->getId()) {
            return null;
        }

        $pathIds = array_values(
            array_filter(
                array_map('intval', explode('/', (string) $category->getPath())),
                static function (int $id): bool {
                    // Ignore the global root node (id 1), keep store root and below.
                    return $id > 1;
                }
            )
        );

        if (empty($pathIds)) {
            return (string) $category->getName();
        }

        // the category itself is already loaded (with its name), no need to refetch it
        $leafId    = (int) $category->getId();
        $namesById = [$leafId => (string) $category->getName()];

        $ancestorIds = array_diff($pathIds, [$leafId]);
        if (! empty($ancestorIds)) {
            $ancestors = $this->categoryCollectionFactory->create()
                ->addAttributeToFilter('entity_id', ['in' => $ancestorIds])
                ->addAttributeToSelect('name');
            foreach ($ancestors as $ancestor) {
                $namesById[(int) $ancestor->getId()] = (string) $ancestor->getName();
            }
        }

        $hierarchyNames = [];
        foreach ($pathIds as $pathId) {
            if (isset($namesById[$pathId]) && $namesById[$pathId] !== '') {
                $hierarchyNames[] = $namesById[$pathId];
            }
        }

        if (empty($hierarchyNames)) {
            return null;
        }

        return implode(' > ', $hierarchyNames);
    }

    private function buildMainSku()
    {
        foreach ($this->buildVariations() as $variation) {
            if ($variation->getMain()) {
                return $variation->getSku();
            }
        }

        return $this->product->getSku();
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

    private function buildSpecifications()
    {
        $specs          = [];
        $attributes     = $this->attributeCollection->getItems();
        $attributeCodes = array_map(
            function ($attribute) {
                return $attribute->getAttributeCode();
            },
            $attributes
        );
        foreach ($this->product->getAttributes() as $attribute) {
            if (! $attribute->getIsUserDefined()) {
                continue;
            }

            if (! in_array($attribute->getAttributeCode(), $attributeCodes)) {
                continue;
            }

            $value = $this->attribute->getAttributeValueLabel(
                $attribute->getAttributeCode(),
                $this->product
            );
            if (! $value) {
                continue;
            }
            $specs[] = $this->objectManager->create(
                Specification::class,
                [
                    'name' => $attribute->getAttributeCode(),
                    'values'    => is_array($value) ? $value : [$value],
                ]
            );
        }

        return $specs;
    }
}
