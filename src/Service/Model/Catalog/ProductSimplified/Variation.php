<?php

declare(strict_types=1);

namespace Gubee\Integration\Service\Model\Catalog\ProductSimplified;

use Gubee\Integration\Helper\Catalog\Attribute;
use Gubee\Integration\Api\Data\ConfigInterface;
use Gubee\Integration\Model\ResourceModel\Catalog\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Gubee\SDK\Enum\Catalog\Product\Attribute\Dimension\Measure\TypeEnum;
use Gubee\SDK\Enum\Catalog\Product\StatusEnum;
use Gubee\SDK\Enum\Catalog\Product\Variation\ConditionEnum;
use Gubee\SDK\Enum\Catalog\Product\Variation\Price\TypeEnum as PriceTypeEnum;
use Gubee\SDK\Model\Catalog\Product\Attribute\AttributeValue;
use Gubee\SDK\Model\Catalog\Product\Attribute\Dimension;
use Gubee\SDK\Model\Catalog\Product\Attribute\Dimension\Measure;
use Gubee\SDK\Model\Catalog\Product\Attribute\Dimension\Weight;
use Gubee\SDK\Model\Catalog\Product\Variation\Media\Image;
use Gubee\SDK\Model\Catalog\ProductV2\Price;
use Gubee\SDK\Model\Catalog\ProductV2\Stock;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Gallery\ReadHandler;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Gubee\SDK\Enum\Catalog\Product\Attribute\Dimension\UnitTime\TypeEnum as UnitTimeTypeEnum;
use Gubee\SDK\Model\Catalog\ProductV2\Specification;

use function array_map;
use function in_array;
use function is_array;
use function pathinfo;
use function preg_replace;
use function sprintf;

use const PATHINFO_FILENAME;

class Variation
{
    protected ProductInterface $product;
    protected ?ProductInterface $parent = null;
    protected \Gubee\SDK\Model\Catalog\ProductV2\Variation $variation;
    protected Attribute $attribute;
    protected ConfigInterface $config;
    /** @var AttributeSearchResultsInterface|ProductAttributeSearchResultsInterface */
    protected $attributeCollection;
    protected ObjectManagerInterface $objectManager;
    protected GetProductSalableQtyInterface $salableQtyGetter;
    protected IsProductSalableInterface $isProductSalableGetter;
    /**
     * @var array product stock qty by stock_id
     */
    protected $productQty = [];

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
        if ($parent instanceof \Magento\Catalog\Api\Data\ProductInterface) {
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
                \Gubee\SDK\Model\Catalog\ProductV2\Variation::class,
            [
                'sku' => $this->buildSku(),
                'main' => $this->buildMain(),
                'name' => $this->buildName(),
                'condition' => $this->buildCondition(),
                'warrantyTime' => $this->buildWarrantyTime(),
                'cost' => $this->buildCost(),
                'dimension' => $this->buildDimension(),
                'prices' => $this->buildPrices(),
                'stocks' => $this->buildStocks(),
                'status' => $this->buildStatus(),
                'images' => $this->buildImages(),
                'variantSpecification' => $this->buildVariantSpecification(),
                'ean' => $this->buildEan(),
                'description' => $this->buildDescription(),
            ]
        );
    }

    /**
     * Verifies if parent is defined and checks its media gallery information
     */
    private function parentHasMedia() : bool
    {
        return !is_null($this->parent) && count($this->parent->getMediaGalleryImages()) > 0;
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
        $type = $this->attribute->getRawAttributeValue(
            'gubee_warranty_time_unit',
            $this->product
        );

        if (empty($type) || is_array($type)) {
            $type = UnitTimeTypeEnum::DAYS();
        } else {
            $type = UnitTimeTypeEnum::fromValue((string) $type);
        }

        $value = $this->attribute->getRawAttributeValue(
            $this->config->getWarrantyTimeAttribute() ?? 'gubee_warranty_time',
            $this->product
        );

        if (empty($value) || is_array($value)) {
            $value = 0;
        }
        return $value;
        $value = max(0, (int) $value);

        if ($value === 0) {
            return 'PT0S';
        }

        if ((string) $type === (string) UnitTimeTypeEnum::HOURS()) {
            return "PT{$value}H";
        }

        if ((string) $type === (string) UnitTimeTypeEnum::MONTH()) {
            // Java Duration does not support months directly; use 30 days per month.
            return 'P' . ($value * 30) . 'D';
        }

        return "P{$value}D";
    }

    protected function buildCondition(): ConditionEnum
    {
        return ConditionEnum::NEW();
    }

    protected function buildCost(): float
    {
        return (float) $this->product->getCost();
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
        return !(bool) $this->parent;
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

        if (array_sum($this->productQty) < 1) {
            return StatusEnum::INACTIVE();
        }

        return $status;
    }
    protected function buildStocks()
    {
        $stocks = [];
        if (($relation = $this->config->getMultistockRelation()) && $this->config->getMultistockEnabled()) {
            foreach ($relation as $stockInfo)
            {
                $stocks = array_merge($stocks, $this->buildStocksSingle($stockInfo['stock_id'], $stockInfo['gubee_code']));
            }
            return $stocks;
        }
        return array_merge($stocks, $this->buildStocksSingle());
    }
    protected function buildStocksSingle($stockId = null, $warehouseId = 'default-warehouse')
    {
        if ($stockId == null) {
            $stockId = $this->config->getDefaultStockId();
        }
        $stocks = [];

        if ($this->isProductSalableGetter->execute($this->product->getSku(), (int)$stockId)) // if product is salable
        {
            $this->productQty[$stockId] = $this->salableQtyGetter->execute($this->product->getSku(), (int) $stockId); // fetch its salable qty
        }

        $stockData = [
            'qty' => (int) ($this->productQty[$stockId] ?? 0),
            'priority' => 1,
            'warehouseId' => $warehouseId
        ];

        $stock = $this->objectManager->create(
            Stock::class,
            $stockData
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
                Specification::class,
                [
                    'name' => $attribute->getAttributeCode(),
                    'values' => is_array($value) ? $value : [$value],
                ]
            );
        }

        return $specs;
    }

    public function getVariation(): \Gubee\SDK\Model\Catalog\ProductV2\Variation
    {
        return $this->variation;
    }
}
