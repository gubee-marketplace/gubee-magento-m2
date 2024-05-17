<?php

declare(strict_types=1);

namespace Gubee\Integration\Model\ResourceModel\Catalog\Product\Attribute;

use Gubee\Integration\Model\Config;
use Magento\Catalog\Api\Data\ProductAttributeSearchResultsInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeSearchResultsInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteria;

class CollectionFactory
{
    protected ProductAttributeRepositoryInterface $productAttributeRepository;
    protected SearchCriteriaBuilder $searchCriteriaBuilder;
    protected SearchCriteria $searchCriteria;
    protected FilterBuilder $filterBuilder;
    protected Config $config;

    public function __construct(
        ProductAttributeRepositoryInterface $productAttributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        Config $config
    ) {
        $this->config                     = $config;
        $this->filterBuilder              = $filterBuilder;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->searchCriteriaBuilder      = $searchCriteriaBuilder;
        $this->searchCriteriaBuilder
            ->addFilter(
                $this->filterBuilder->setField('attribute_code')
                    ->setValue('gubee')
                    ->setConditionType('neq')
                    ->create()
            )->addFilter(
                $this->filterBuilder->setField('attribute_code')
                ->setValue('gubee_%')
                ->setConditionType('nlike')
                ->create()
            )->addFilter(
                $this->filterBuilder->setField('attribute_code')
                ->setValue($this->config->getEanAttribute())
                ->setConditionType('neq')
                ->create()
            )->addFilter(
                $this->filterBuilder->setField('attribute_code')
                ->setValue($this->config->getWidthAttribute())
                ->setConditionType('neq')
                ->create()
            )->addFilter(
                $this->filterBuilder->setField('attribute_code')
                ->setValue($this->config->getHeightAttribute())
                ->setConditionType('neq')
                ->create()
            )->addFilter(
                $this->filterBuilder->setField('attribute_code')
                ->setValue($this->config->getDepthAttribute())
                ->setConditionType('neq')
                ->create()
            )->addFilter(
                $this->filterBuilder->setField('attribute_code')
                ->setValue($this->config->getMeasureUnitAttribute())
                ->setConditionType('neq')
                ->create()
            )->addFilter(
                $this->filterBuilder->setField('attribute_code')
                ->setValue($this->config->getCrossDockingTimeAttribute())
                ->setConditionType('neq')
                ->create()
            )->addFilter(
                $this->filterBuilder->setField('attribute_code')
                ->setValue($this->config->getNbmAttribute())
                ->setConditionType('neq')
                ->create()
            )->addFilter(
                $this->filterBuilder->setField('attribute_code')
                ->setValue($this->config->getBrandAttribute())
                ->setConditionType('neq')
                ->create()
            )
            ->addFilter(
                $this->filterBuilder->setField('frontend_label')
                    ->setValue("1")
                    ->setConditionType('notnull')
                    ->create()
            )
            ->addFilter(
                $this->filterBuilder->setField('frontend_input')
                    ->setValue('gallery')
                    ->setConditionType('neq')
                    ->create()
            )
            ->addFilter(
                $this->filterBuilder->setField('backend_type')
                    ->setValue('static')
                    ->setConditionType('neq')
                    ->create()
            )
            ->addFilter(
                $this->filterBuilder->setField('is_user_defined')
                    ->setValue("1")
                    ->setConditionType('eq')
                    ->create()
            )
            ->addFilter(
                $this->filterBuilder->setField('attribute_code')
                    ->setValue(
                        $this->config->getBlacklistAttribute()
                    )
                    ->setConditionType('nin')
                    ->create()
            );
    }

    /**
     * Create a new collection of product attributes.
     *
     * @return AttributeSearchResultsInterface|ProductAttributeSearchResultsInterface .
     */
    public function create()
    {
        return $this->productAttributeRepository->getList(
            $this->searchCriteriaBuilder->create()
        );
    }

    /**
     * Append a filter to the collection.
     *
     * @param mixed $value
     * @param mixed $conditionType
     */
    public function appendFilter(
        $value,
        string $field,
        $conditionType = 'eq'
    ): self {
        $this->searchCriteriaBuilder->addFilter(
            $this->filterBuilder->setField($field)
                ->setValue($value)
                ->setConditionType($conditionType)
                ->create()
        );

        return $this;
    }

    public function getProductAttributeRepository(): ProductAttributeRepositoryInterface
    {
        return $this->productAttributeRepository;
    }

    public function setProductAttributeRepository(ProductAttributeRepositoryInterface $productAttributeRepository): self
    {
        $this->productAttributeRepository = $productAttributeRepository;
        return $this;
    }
}
