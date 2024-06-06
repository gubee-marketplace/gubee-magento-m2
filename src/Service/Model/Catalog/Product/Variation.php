<?php

declare(strict_types=1);

namespace Gubee\Integration\Service\Model\Catalog\Product;

use Gubee\Integration\Helper\Catalog\Attribute;
use Gubee\Integration\Api\Data\ConfigInterface;
use Gubee\Integration\Model\ResourceModel\Catalog\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Gubee\SDK\Enum\Catalog\Product\Attribute\Dimension\Measure\TypeEnum;
use Gubee\SDK\Enum\Catalog\Product\Attribute\Dimension\UnitTime\TypeEnum as UnitTimeTypeEnum;
use Gubee\SDK\Enum\Catalog\Product\StatusEnum;
use Gubee\SDK\Enum\Catalog\Product\Variation\Price\TypeEnum as PriceTypeEnum;
use Gubee\SDK\Model\Catalog\Product\Attribute\AttributeValue;
use Gubee\SDK\Model\Catalog\Product\Attribute\Dimension;
use Gubee\SDK\Model\Catalog\Product\Attribute\Dimension\Measure;
use Gubee\SDK\Model\Catalog\Product\Attribute\Dimension\UnitTime;
use Gubee\SDK\Model\Catalog\Product\Attribute\Dimension\Weight;
use Gubee\SDK\Model\Catalog\Product\Variation\Media\Image;
use Gubee\SDK\Model\Catalog\Product\Variation\Price;
use Gubee\SDK\Model\Catalog\Product\Variation\Stock;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Gallery\ReadHandler;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;

use function array_map;
use function in_array;
use function is_array;
use function pathinfo;
use function preg_replace;
use function sprintf;

use const PATHINFO_FILENAME;

class Variation
{
    public const SEPARATOR = '-|GI|-';
    /**
     * @var ProductInterface
     */
    protected  $product;
    protected  $parent = null;
    protected  $variation;
    protected  $attribute;
    protected  $config;
    /** @var AttributeSearchResultsInterface|ProductAttributeSearchResultsInterface */
    protected $attributeCollection;
    protected  $objectManager;
    protected  $salableQtyGetter;
    protected  $isProductSalableGetter;
    /**
     * @var float product stock qty
     * default it to 0
     */
    protected $productQty = 0;

    public function __construct(
        ProductInterface $product,
        Attribute $attribute,
        ConfigInterface $config,
        ObjectManagerInterface $objectManager,
        AttributeCollectionFactory $attributeCollectionFactory,
        GetProductSalableQtyInterface $salableQtyGetter,
        IsProductSalableInterface $isProductSalableGetter,
        ReadHandler $galleryReadHandler,
        ?ProductInterface $parent = null
    )
    {
        if ($parent) {
            $this->parent = $parent;
        }
        $this->attributeCollection = $attributeCollectionFactory->create();
        $galleryReadHandler->execute($product);
        $this->product = $product;
        $this->attribute = $attribute;
        $this->config = $config;
        $this->objectManager = $objectManager;
        $this->salableQtyGetter = $salableQtyGetter;
        $this->isProductSalableGetter = $isProductSalableGetter;
        $this->variation = $this->objectManager->create(
                \Gubee\SDK\Model\Catalog\Product\Variation::class,
            [
                'skuId' => $this->buildSkuId(),
                'images' => $this->buildImages(),
                'dimension' => $this->buildDimension(),
                'handlingTime' => $this->buildHandlingTime(),
                'name' => $this->buildName(),
                'sku' => $this->buildSku(),
                'warrantyTime' => $this->buildWarrantyTime(),
                'cost' => $this->buildCost(),
                'description' => $this->buildDescription(),
                'ean' => $this->buildEan() ?: '',
                'main' => $this->buildMain() ?: '',
                'prices' => $this->buildPrices(),
                'stocks' => $this->buildStocks(),
                'status' => $this->buildStatus(),
                'variantSpecification' => $this->buildVariantSpecification(),
            ]
        );
    }

    protected function buildSkuId()
    {
        return $this->parent ? sprintf(
            "%s%s%s",
            $this->parent->getSku() ?: $this->product->getSku(),
            self::SEPARATOR,
            $this->product->getSku()
        ) : $this->product->getSku();
    }
    /**
     * Verifies if parent is defined and checks its media gallery information
     * @return bool
     */
    private function parentHasMedia() : bool
    {
        return !is_null($this->parent) ? count($this->parent->getMediaGalleryImages()) > 0 : false;
    }

    protected function buildImages()
    {
        $images = [];
        if (count($this->product->getMediaGalleryImages()) == 0 && !$this->parentHasMedia()) {
            return [
                $this->createPlaceholder(),
            ];
        }
        $main = true;
        //switch between child and parent depending on its gallery status
        $productToUploadImages = count($this->product->getMediaGalleryImages()) == 0 ? $this->parent : $this->product;
        foreach ($productToUploadImages->getMediaGalleryImages() as $key => $image) {
            $images[] = $this->objectManager->create(
                Image::class,
                [
                    // remove protocol from image url
                    'url' => preg_replace('/^https?:/', '', $image->getUrl()),
                    'order' => $image->getPosition() ?: $key,
                    'name' => $image->getLabel() ?: pathinfo($image->getFile(), PATHINFO_FILENAME),
                    'id' => $image->getId(),
                    'main' => $main,
                ]
            );
            $main = false;
        }
        return $images;
    }

    private function createPlaceholder()
    {
        return $this->objectManager->create(
            Image::class,
            [
                'url' => '#',
                'order' => 0,
                'name' => 'Placeholder',
                'id' => 0,
                'main' => true,
            ]
        );
    }

    protected function buildDimension()
    {
        $height = $this->objectManager->create(
            Measure::class,
            [
                'value' => (float) $this->attribute->getRawAttributeValue(
                    $this->config->getHeightAttribute(),
                    $this->product
                ),
                'type' => TypeEnum::fromValue($this->config->getMeasureUnitAttribute()),
            ]
        );
        $width = $this->objectManager->create(
            Measure::class,
            [
                'value' => (float) $this->attribute->getRawAttributeValue(
                    $this->config->getWidthAttribute(),
                    $this->product
                ),
                'type' => TypeEnum::fromValue($this->config->getMeasureUnitAttribute()),
            ]
        );
        $depth = $this->objectManager->create(
            Measure::class,
            [
                'value' => (float) $this->attribute->getRawAttributeValue(
                    $this->config->getDepthAttribute(),
                    $this->product
                ),
                'type' => TypeEnum::fromValue($this->config->getMeasureUnitAttribute()),
            ]
        );
        $weight = $this->objectManager->create(
            Weight::class,
            [
                'value' => (float) $this->attribute->getRawAttributeValue(
                    'weight',
                    $this->product
                ),
                'type' => $this->config->getWeightUnit(),
            ]
        );

        return $this->objectManager->create(
            Dimension::class,
            [
                'height' => $height,
                'width' => $width,
                'depth' => $depth,
                'weight' => $weight,
            ]
        );
    }

    protected function buildHandlingTime()
    {
        $type =
            $this->attribute->getRawAttributeValue(
                'gubee_handling_time_unit',
                $this->product
            );
        if (empty($type) || is_array($type)) {
            $type = UnitTimeTypeEnum::DAYS();
        } else {
            $type = UnitTimeTypeEnum::fromValue($type);
        }

        return $this->objectManager->create(
            UnitTime::class,
            [
                'value' => (float) $this->attribute->getRawAttributeValue(
                    'gubee_handling_time',
                    $this->product
                ),
                'type' => $type,
            ]
        );
    }

    protected function buildName()
    {
        return $this->product->getName();
    }

    protected function buildSku()
    {
        return $this->product->getSku();
    }

    protected function buildWarrantyTime()
    {
        $type =
            $this->attribute->getRawAttributeValue(
                'gubee_warranty_time_unit',
                $this->product
            );
        if (empty($type) || is_array($type)) {
            $type = UnitTimeTypeEnum::DAYS();
        } else {
            $type = UnitTimeTypeEnum::fromValue($type);
        }

        return $this->objectManager->create(
            UnitTime::class,
            [
                'value' => (float) $this->attribute->getRawAttributeValue(
                    'gubee_warranty_time',
                    $this->product
                ),
                'type' => $type,
            ]
        );
    }

    protected function buildCost()
    {
        return $this->product->getCost();
    }

    protected function buildDescription()
    {
        $description = $this->product->getDescription() ?: $this->attribute->getRawAttributeValue(
            'description',
            $this->product
        );

        return is_array($description) ? implode("\n", $description) : $description; 
    }

    protected function buildEan()
    {
        return $this->attribute->getRawAttributeValue(
            $this->config->getEanAttribute(),
            $this->product
        ) ?: null;
    }

    protected function buildMain()
    {
        return $this->parent ? false : true;
    }

    protected function buildPrices()
    {
        $prices = [];
        $price = $this->objectManager->create(
            Price::class,
            [
                'type' => PriceTypeEnum::DEFAULT (),
                'value' => (float) $this->attribute->getRawAttributeValue(
                    $this->config->getPriceAttribute(),
                    $this->product
                ),
            ]
        );

        $prices[] = $price;

        return $prices;
    }

    private function buildStatus()
    {
        $status = StatusEnum::ACTIVE();
        if (!$this->product->isSalable()) {
            return StatusEnum::INACTIVE();
        }

        if ($this->productQty < 1) {
            return StatusEnum::INACTIVE();
        }

        return $status;
    }

    protected function buildStocks()
    {
        $stocks = [];
        $type = $this->attribute->getRawAttributeValue(
            'gubee_cross_docking_time_unit',
            $this->product
        );
        if (empty($type) || is_array($type)) {
            $type = UnitTimeTypeEnum::DAYS();
        } else {
            $type = UnitTimeTypeEnum::fromValue($type);
        }

        $crossDockingTime = $this->objectManager->create(
            UnitTime::class,
            [
                'value' => (int) $this->attribute->getRawAttributeValue(
                    'gubee_cross_docking_time',
                    $this->product
                ) ?: -1,
                'type' => $type,
            ]
        );
        if ($this->isProductSalableGetter->execute($this->product->getSku(), $this->config->getDefaultStockId())) // if product is salable
        {
            $this->productQty = $this->salableQtyGetter->execute($this->product->getSku(), $this->config->getDefaultStockId()); // fetch its salable qty
        }
        $stock = $this->objectManager->create(
            Stock::class,
            [
                'qty' => $this->productQty,
                'crossDockingTime' => $crossDockingTime,
            ]
        );

        $stocks[] = $stock;

        return $stocks;
    }

    protected function buildVariantSpecification()
    {
        $specs = [];
        $attributes = $this->attributeCollection->getItems();
        
        $attributeCodes = array_map(
            function ($attribute) {
                return $attribute->getAttributeCode();
            },
            $attributes
        );
        /**
         * @var \Gubee\SDK\Model\Catalog\Product\Variation $variations[]
         */
        $sAttributeCodes = [];
        if (!is_null($this->parent)) {
            $configurableAttributes = $this->parent->getTypeInstance()->getConfigurableOptions($this->parent);
            foreach ($configurableAttributes as $a) {
                foreach ($a as $p) {
                    $sAttributeCodes[] = $p['attribute_code'];
                }
            }
            $sAttributeCodes = array_unique($sAttributeCodes);
        }
        /**
         * @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute
         */
        foreach ($this->product->getAttributes() as $attribute) {
            if (!$attribute->getIsUserDefined()) {
                continue;
            }

            if (!in_array($attribute->getAttributeCode(), $attributeCodes) || !in_array($attribute->getAttributeCode(), $sAttributeCodes)) {
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

    public function getVariation(): \Gubee\SDK\Model\Catalog\Product\Variation
    {
        return $this->variation;
    }
}
