<?php

declare(strict_types=1);

namespace Gubee\Integration\Setup\Patch\Data;

use Gubee\Integration\Model\Catalog\Product\Attribute\Source\HandlingTime;
use Gubee\Integration\Model\Catalog\Product\Attribute\Source\Origin;
use Gubee\Integration\Setup\AbstractMigration;
use Gubee\Integration\Setup\Migration\Context;
use Gubee\Integration\Setup\Migration\Facade\ProductAttribute;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Eav\Model\Entity\Attribute\Source\Table;

use function __;
use function array_merge;
use function sprintf;

class GubeeCatalogProductAttribute extends AbstractMigration
{
    protected ProductAttribute $productAttribute;
    /** @var array<string,array<string,mixed>> */
    protected array $attributes = [
        'gubee'                         => [
            'type'                    => 'int',
            'label'                   => 'Send product to Gubee',
            'input'                   => 'boolean',
            'source'                  => Boolean::class,
            'used_in_product_listing' => true,
        ],
        'gubee_sync'                    => [
            'type'    => 'int',
            'input'   => 'boolean',
            'label'   => 'Keep synced with Gubee',
            'source'  => Boolean::class,
            'comment' => 'If checked, this product will automatically be synced with Gubee on every save.',
        ],
        'gubee_brand'                   => [
            'type'   => 'int',
            'label'  => 'Brand',
            'input'  => 'select',
            'source' => Table::class,
        ],
        'gubee_origin'                  => [
            'type'   => 'varchar',
            'label'  => 'Origin',
            'input'  => 'select',
            'source' => Origin::class,
        ],
        'gubee_nbm'                     => [
            'type'  => 'varchar',
            'label' => 'NBM',
        ],
        'gubee_ean'                     => [
            'type'  => 'varchar',
            'label' => 'EAN',
        ],
        'gubee_width'                   => [
            'type'  => 'decimal',
            'label' => 'Width',
        ],
        'gubee_height'                  => [
            'type'  => 'decimal',
            'label' => 'Height',
        ],
        'gubee_depth'                   => [
            'type'  => 'decimal',
            'label' => 'Depth',
        ],
        'gubee_handling_time'           => [
            'type'  => 'int',
            'label' => 'Handling Time',
        ],
        'gubee_handling_time_unit'      => [
            'type'   => 'varchar',
            'label'  => 'Handling Time Unit',
            'input'  => 'select',
            'source' => HandlingTime::class,
        ],
        'gubee_warranty_time'           => [
            'type'  => 'int',
            'label' => 'Warranty Time',
        ],
        'gubee_warranty_time_unit'      => [
            'type'   => 'varchar',
            'label'  => 'Warranty Time Unit',
            'input'  => 'select',
            'source' => HandlingTime::class,
        ],
        'gubee_price'                   => [
            'type'  => 'decimal',
            'label' => 'Price',
            'input' => 'price',
        ],
        'gubee_cross_docking_time'      => [
            'type'  => 'int',
            'label' => 'Cross Docking Time',
        ],
        'gubee_cross_docking_time_unit' => [
            'type'    => 'varchar',
            'label'   => 'Cross Docking Time Unit',
            'input'   => 'select',
            'source'  => HandlingTime::class,
            'default' => 'DAYS',
            'note'    => 'Time to prepare the product to be shipped after the'
            . ' order is placed, if none is set days will be used by default.',
        ],
        'gubee_integration_status'      => [
            'type'         => 'int',
            'label'        => 'Integration Status',
            'user_defined' => false,
            'visible'      => false,
            'input'        => 'select',
            'default'      => 0,
            'note'         => 'Status of the product integration with Gubee.',
        ],
    ];

    public function __construct(
        ProductAttribute $productAttribute,
        Context $context
    ) {
        $this->productAttribute = $productAttribute;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(): void
    {
        foreach ($this->attributes as $attributeCode => $attrValue) {
            $this->context->getLogger()
                ->info(
                    sprintf(
                        "Creating/Updating attribute '%s'",
                        $attributeCode
                    )
                );
            $attrValue = array_merge(
                [
                    'global'          => ScopedAttributeInterface::SCOPE_STORE,
                    'user_defined'    => true,
                    'required'        => false,
                    'is_used_in_grid' => true,
                    'visible'         => true,
                    'input'           => 'text',
                    'group'           => 'Gubee',
                ],
                $attrValue,
                [
                    'label' => $this->getAttributeLabel($attrValue['label']),
                ]
            );
            if ($this->productAttribute->exists($attributeCode)) {
                $this->context->getLogger()->info(
                    __(
                        "Updating attribute '%1'",
                        $attributeCode
                    )->__toString()
                );
                $this->productAttribute->update(
                    $attributeCode,
                    $attrValue
                );
            } else {
                $this->context->getLogger()->info(
                    __(
                        "Creating attribute '%1'",
                        $attributeCode
                    )->__toString()
                );
                $this->productAttribute->create(
                    $attributeCode,
                    $attrValue
                );
            }
        }
        $this->context->getLogger()->info(
            "Attributes created/updated"
        );
    }

    public function getAttributeLabel(string $label): string
    {
        if ($label === 'gubee') {
            return 'Gubee';
        }
        return sprintf(
            "Gubee > %s",
            $label
        );
    }

    protected function rollback()
    {
        foreach ($this->attributes as $attributeCode => $attrValue) {
            // remove any custom attribute source model
            if (isset($attrValue['source'])) {
                $this->productAttribute->update(
                    $attributeCode,
                    [
                        'source' => null,
                    ]
                );
            }
        }
    }
}
