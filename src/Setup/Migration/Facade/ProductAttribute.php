<?php

declare(strict_types=1);

namespace Gubee\Integration\Setup\Migration\Facade;

use Gubee\Integration\Setup\Migration\Common\AbstractEavAttribute;
use Gubee\Integration\Setup\Migration\Common\EavAttribute\Context;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Eav\Model\AttributeManagement;
use Magento\Eav\Model\AttributeSetRepository;
use Magento\Eav\Model\Entity\Attribute\Group as AttributeGroup;
use Magento\Eav\Model\Entity\Attribute\Set as AttributeSet;
use Magento\Eav\Model\Entity\Attribute\Source\Table;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

use function __;
use function array_merge;
use function intval;
use function is_array;
use function is_int;
use function is_object;

class ProductAttribute extends AbstractEavAttribute
{
    public const ENTITY_TYPE = ProductAttributeInterface::ENTITY_TYPE_CODE;
    protected AttributeManagement $attributeManagement;
    protected AttributeSetRepository $attributeSetRepository;
    protected ProductAction $productAction;

    public function __construct(
        LoggerInterface $logger,
        Context $context,
        AttributeManagement $attributeManagement,
        AttributeSetRepository $attributeSetRepository,
        ProductAction $productAction
    ) {
        parent::__construct($context, $logger);
        $this->attributeManagement    = $attributeManagement;
        $this->attributeSetRepository = $attributeSetRepository;
        $this->productAction          = $productAction;
    }

    /**
     * Shorthand to quickly create a dropdown-type attribute
     *
     * @param string $code
     * @param array<string, mixed>  $values
     * @param array<string, mixed>  $config
     */
    public function createDropdown($code, string $label, array $values, array $config = []): void
    {
        $this->create(
            $code,
            array_merge(
                [
                    'label'        => $label,
                    'input'        => 'select',
                    'type'         => 'int',
                    'source_model' => Table::class,
                    'filterable'   => 1,
                    'required'     => false,
                    'option'       => ['values' => $values],
                    'user_defined' => true,
                ],
                $config
            )
        );
    }

    /**
     * Assign an attribute to some attribute set (or default if none specified)
     *
     * @param string $attributeCode
     * @param array<string, mixed>|int|string|AttributeSet $attributeSet
     * @param int|string|AttributeGroup $group
     * @param string $after
     * @return mixed
     */
    public function assignToAttributeSet(
        $attributeCode,
        $attributeSet = null,
        $group = null,
        $after = null
    ) {
        if (is_array($attributeSet)) {
            $this->assignToAttributeSetLegacy(
                $attributeCode,
                $attributeSet
            );
            return;
        }

        $attributeSetId   = $this->resolveAttributeSetId(
            $attributeSet
        );
        $attributeGroupId = $this->resolveGroupId(
            $group,
            $attributeSet
        );
        $sortOrder        = $after ? $this->getSortOrder(
            $after,
            $attributeSet
        ) + 1 : 999;

        $this->attributeManagement->assign(
            self::ENTITY_TYPE,
            $attributeSetId,
            $attributeGroupId,
            $attributeCode,
            $sortOrder
        );
    }

    /**
     * Old implementation of attribute set assign
     * for retrocompatibility purposes only
     *
     * @param array<int|string, mixed> $options
     */
    private function assignToAttributeSetLegacy(
        string $attributeCode,
        array $options = []
    ): void {
        $attributeSetId = intval($options['attribute_set_id'] ?:
            $this->getEavSetup()->getDefaultAttributeSetId(
                self::ENTITY_TYPE
            ));

        $attributeGroupId = $options['group_id'] ?:
            $this->getDefaultGroupId($attributeSetId);
        $sortOrder        = $options['sort_order'] ?? 999;

        $this->attributeManagement->assign(
            self::ENTITY_TYPE,
            $attributeSetId,
            $attributeGroupId,
            $attributeCode,
            $sortOrder
        );
    }

    /**
     * Remove given attribute from given attribute set
     */
    public function unassignFromAttributeSet(
        string $attributeCode,
        ?int $attributeSetId = null
    ): void {
        $attributeSetId = $attributeSetId ?: $this->getEavSetup()
            ->getDefaultAttributeSetId(self::ENTITY_TYPE);

        $this->attributeManagement->unassign(
            (string) $attributeSetId,
            $attributeCode
        );
    }

    /**
     * Update given attribute value for multiple products at once
     *
     * @param array<int> $entityIds
     * @param array<int|string, mixed> $data
     */
    public function massUpdate($entityIds, $data): void
    {
        $this->productAction->updateAttributes(
            $entityIds,
            $data,
            self::SCOPE_STORE
        );
    }

    /**
     * Return the id of given attribute set
     *
     * @param int|string|AttributeSet $attributeSet
     */
    private function resolveAttributeSetId($attributeSet = null): int
    {
        if ($attributeSet === null) {
            return $this->getDefaultAttributeSetId();
        }

        if (is_object($attributeSet)) {
            return (int) $attributeSet->getId();
        }

        if (! is_int($attributeSet)) {
            $attributeSetId = $this->getConnection()->fetchOne(<<<SQL
                SELECT attribute_set_id FROM {$this->getTableName('eav_attribute_set')}
                WHERE attribute_set_name = ? AND entity_type_id = ?
            SQL, [$attributeSet, $this->getEntityTypeId()]);

            if (empty($attributeSetId)) {
                throw new NoSuchEntityException(__("Attribute Set with name $attributeSet not found"));
            }

            return (int) $attributeSetId;
        }

        return $attributeSet;
    }

    /**
     * Resolve given attribute set group into its id
     *
     * @param int|string|AttributeGroup $group
     * @param int|string|AttributeSet $attributeSet
     * @throws NoSuchEntityException If group is not found.
     */
    private function resolveGroupId($group = null, $attributeSet = null): int
    {
        if ($group === null) {
            return $this->getDefaultGroupId($attributeSet);
        }

        if (is_object($group)) {
            return (int) $group->getId();
        }

        if (! is_int($group)) {
            $groupId = $this->getConnection()->fetchOne(<<<SQL
                SELECT attribute_group_id FROM {$this->getTableName('eav_attribute_group')}
                WHERE attribute_group_name = ? AND attribute_set_id = ?
            SQL, [$group, $this->resolveAttributeSetId($attributeSet)]);

            if (empty($groupId)) {
                throw new NoSuchEntityException(
                    __(
                        "Attribute Group with name '%s' not found",
                        $group
                    )
                );
            }

            return (int) $groupId;
        }

        return $group;
    }

    /**
     * Retrieve default product attribute set id
     */
    private function getDefaultAttributeSetId(): int
    {
        return (int) $this->getEavSetup()->getDefaultAttributeSetId(self::ENTITY_TYPE);
    }

    /**
     * Retrieve default group id of given attribute set
     *
     * @param int|string|AttributeSet $attributeSet
     */
    private function getDefaultGroupId($attributeSet = null): int
    {
        $attributeSetId = $this->resolveAttributeSetId($attributeSet);
        $attributeSet   = $this->attributeSetRepository->get($attributeSetId);

        return (int) $attributeSet->getDefaultGroupId(); /* @phpstan-ignore-line */
    }

    /**
     * Retrieve current sort position of given attribute
     *
     * @param string $attributeCode
     * @param string|int|AttributeSet $attributeSet
     * @return int
     */
    private function getSortOrder($attributeCode, $attributeSet = null)
    {
        $entityTypeId   = $this->getEntityTypeId();
        $attributeSetId = $this->resolveAttributeSetId($attributeSet);

        return (int) $this->getConnection()->fetchOne(<<<SQL
            SELECT sort_order FROM {$this->getTableName('eav_entity_attribute')}
            WHERE entity_type_id = ? AND attribute_set_id = ? AND attribute_id = (
                SELECT attribute_id FROM {$this->getTableName('eav_attribute')}
                WHERE attribute_code = ?
            )
        SQL, [$entityTypeId, $attributeSetId, $attributeCode]) ?: 999;
    }
}
