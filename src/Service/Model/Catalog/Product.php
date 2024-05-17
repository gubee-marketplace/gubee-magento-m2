<?php

declare(strict_types=1);

namespace Gubee\Integration\Service\Model\Catalog;

use Gubee\Integration\Api\Enum\MainCategoryEnum;
use Gubee\Integration\Helper\Catalog\Attribute;
use Gubee\Integration\Model\Config;
use Gubee\Integration\Model\ResourceModel\Catalog\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Gubee\Integration\Service\Model\Catalog\Product\Variation;
use Gubee\SDK\Enum\Catalog\Product\Attribute\OriginEnum;
use Gubee\SDK\Enum\Catalog\Product\StatusEnum;
use Gubee\SDK\Enum\Catalog\Product\TypeEnum;
use Gubee\SDK\Model\Catalog\Category;
use Gubee\SDK\Model\Catalog\Product\Attribute\AttributeValue;
use Gubee\SDK\Model\Catalog\Product\Attribute\Brand;
use Gubee\SDK\Resource\Catalog\Product\ValidateResource;
use Gubee\SDK\Resource\Catalog\ProductResource;
use Magento\Catalog\Api\Data\ProductAttributeSearchResultsInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Api\Data\AttributeSearchResultsInterface;
use Magento\Framework\ObjectManagerInterface;

use function array_filter;
use function array_map;
use function array_merge;
use function count;
use function in_array;
use function is_array;

class Product
{
    /**
     * @var ProductInterface
     */
    protected ProductInterface $product;
    protected \Gubee\SDK\Model\Catalog\Product $gubeeProduct;
    protected ProductResource $productResource;
    protected Attribute $attribute;
    protected Config $config;
    protected ObjectManagerInterface $objectManager;
    /** @var AttributeSearchResultsInterface|ProductAttributeSearchResultsInterface */
    protected $attributeCollection;
    protected CollectionFactory $categoryCollectionFactory;
    protected StockItemInterface $stockItem;
    protected ValidateResource $validateResource;
    private ?array $variations = null;

    public function __construct(
        ProductInterface $product,
        Attribute $attribute,
        Config $config,
        ProductResource $productResource,
        ValidateResource $validateResource,
        ObjectManagerInterface $objectManager,
        CollectionFactory $categoryCollectionFactory,
        AttributeCollectionFactory $attributeCollectionFactory,
        StockRegistryInterface $stockRegistry
    )
    {
        $this->validateResource = $validateResource;
        $this->attributeCollection = $attributeCollectionFactory->create();
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->stockItem = $stockRegistry->getStockItem($product->getId());
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->objectManager = $objectManager;
        $this->attribute = $attribute;
        $this->config = $config;
        $this->product = $product;
        $this->productResource = $productResource;
        $this->gubeeProduct = $objectManager->create(
                \Gubee\SDK\Model\Catalog\Product::class,
            array_filter(
                [
                    'id' => $this->buildId(),
                    'mainCategory' => $this->buildMainCategory(),
                    'mainSku' => $this->buildMainSku(),
                    'origin' => $this->buildOrigin(),
                    'status' => $this->buildStatus(),
                    'type' => $this->buildType(),
                    'name' => $this->buildName(),
                    'nbm' => $this->buildNbm(),
                    'categories' => $this->buildCategories(),
                    'specifications' => $this->buildSpecifications(),
                    'brand' => $this->buildBrand(),
                    'variations' => $this->buildVariations(),
                ]
            )
        );
        if ($this->gubeeProduct->getType() == TypeEnum::VARIANT()) {
            $this->gubeeProduct->setVariantAttributes(
                $this->buildVariantAttributes()
            );
        }
    }

    public function validate()
    {
        return $this->validateResource->create($this->gubeeProduct);
    }

    public function save()
    {
        $this->getGubeeProduct()->save();
    }

    public function desativate()
    {
        $this->getGubeeProduct()->setStatus(
            StatusEnum::INACTIVE()
        );
        $this->getGubeeProduct()->save();
        foreach ($this->getGubeeProduct()->getVariations() as $variation) {
            foreach ($variation->getStocks() as $stock) {
                $stock->setQty(0);
                $stock->save(
                    $this->getGubeeProduct()->getId(),
                    $variation->getSkuId()
                );
            }
        }
    }

    public function saveStock()
    {
        foreach ($this->getGubeeProduct()->getVariations() as $variation) {
            foreach ($variation->getStocks() as $stock) {
                $stock->save(
                    $this->getGubeeProduct()->getId(),
                    $variation->getSkuId()
                );
            }
        }
    }

    public function savePrice()
    {
        foreach ($this->getGubeeProduct()->getVariations() as $variation) {
            foreach ($variation->getPrices() as $price) {
                $price->save(
                    $this->getGubeeProduct()->getId(),
                    $variation->getSkuId()
                );
            }
        }
    }

    private function buildBrand()
    {
        $brand = $this->attribute->getAttributeValueLabel(
            $this->config->getBrandAttribute(),
            $this->product
        );
        if (!$brand) {
            return null;
        }

        return $this->getObjectManager()->create(
            Brand::class,
            [
                'name' => $brand,
            ]
        );
    }

    private function buildId()
    {
        return $this->product->getId();
    }

    private function buildMainCategory()
    {
        $categories = $this->product->getCategoryIds();
        $collection = $this->categoryCollectionFactory->create()
            ->addAttributeToFilter('entity_id', ['in' => $categories])
            ->addAttributeToSelect('*');
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
        if (!$category->getId()) {
            // get root category
            $category = $this->categoryCollectionFactory->create()
                ->addAttributeToFilter('level', 2)
                ->addAttributeToSelect('*')
                ->getFirstItem();
        }
        return $this->getObjectManager()->create(
            Category::class,
            [
                'id' => $category->getId(),
                'name' => $category->getName(),
            ]
        );
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

    private function buildOrigin()
    {
        return $this->attribute->getRawAttributeValue(
            'gubee_origin',
            $this->product
        ) ?: OriginEnum::NATIONAL();
    }

    private function buildStatus()
    {
        return $this->product->getStatus() == \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED ? 
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

    private function buildName()
    {
        return $this->attribute->getAttributeValueLabel(
            'name',
            $this->product
        );
    }

    private function buildNbm()
    {
        return $this->attribute->getAttributeValueLabel(
            $this->config->getNbmAttribute(),
            $this->product
        );
    }

    private function buildCategories()
    {
        $categories = $this->product->getCategoryIds();
        $collection = $this->categoryCollectionFactory->create()
            ->addAttributeToFilter('entity_id', ['in' => $categories])
            ->addAttributeToSelect('*');
        if (!$collection->count()) {
            $category = $this->categoryCollectionFactory->create()
                ->addAttributeToFilter('level', 2)
                ->addAttributeToSelect('*')
                ->getFirstItem();
            return [
                $this->getObjectManager()->create(
                    Category::class,
                    [
                        'id' => $category->getId(),
                        'name' => $category->getName(),
                    ]
                ),
            ];
        }
        $categories = [];

        foreach ($collection as $key => $category) {
            $categories[$key] = $this->getObjectManager()->create(
                Category::class,
                [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                ]
            );
        }

        return $categories;
    }

    private function buildSpecifications()
    {
        $specs = [];
        $attributes = $this->attributeCollection->getItems();
        $attributeCodes = array_map(
            function ($attribute) {
                return $attribute->getAttributeCode();
            },
            $attributes
        );
        foreach ($this->product->getAttributes() as $attribute) {
            if (!$attribute->getIsUserDefined()) {
                continue;
            }

            if (!in_array($attribute->getAttributeCode(), $attributeCodes)) {
                continue;
            }

            $value = $this->attribute->getAttributeValueLabel(
                $attribute->getAttributeCode(),
                $this->product
            );
            if (!$value) {
                continue;
            }
            $specs[] = $this->objectManager->create(
                AttributeValue::class,
                [
                    'attribute' => $attribute->getAttributeCode(),
                    'values' => is_array($value) ? $value : [$value],
                ]
            );
        }

        return $specs;
    }

    private function buildVariantAttributes()
    {
        $variations = $this->buildVariations();
        $attrs = [];
        foreach ($variations as $variation) {
            foreach ($variation->getVariantSpecification() as $spec) {
                if (isset($attrs[$spec->getAttribute()])) {
                    $attrs[$spec->getAttribute()] = array_merge(
                        $attrs[$spec->getAttribute()],
                        $spec->getValues()
                    );
                } else {
                    $attrs[$spec->getAttribute()] = $spec->getValues();
                }
            }
        }

        foreach ($attrs as $attribute => $values) {
            $attrs[$attribute] = $this->objectManager->create(
                AttributeValue::class,
                [
                    'attribute' => $attribute,
                    'values' => $values,
                ]
            );
        }

        return $attrs;
    }

    private function buildVariations()
    {
        if ($this->variations) {
            return $this->variations;
        }
        if ($this->product->getTypeId() != Configurable::TYPE_CODE) {
            $variation = $this->objectManager->create(
                Variation::class,
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
        $main = true;
        $children = $this->product
            ->getTypeInstance()
            ->getUsedProducts($this->product);
        foreach ($children as $child) {
            $variation = $this->objectManager->create(
                Variation::class,
                [
                    'product' => $child,
                    'parent' => $this->product,
                ]
            )->getVariation();
            $variation->setMain($main);
            $variations[] = $variation;
            $main = false;
        }

        $this->variations = $variations;
        return $variations;
    }

    public function getObjectManager(): ObjectManagerInterface
    {
        return $this->objectManager;
    }

    public function getGubeeProduct(): \Gubee\SDK\Model\Catalog\Product
    {
        return $this->gubeeProduct;
    }
}
