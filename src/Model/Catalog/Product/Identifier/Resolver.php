<?php
declare(strict_types=1);

namespace Gubee\Integration\Model\Catalog\Product\Identifier;

use Gubee\Integration\Api\Data\ConfigInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Search\Search;
use Psr\Log\LoggerInterface;

class Resolver
{
    private ConfigInterface $config;
    
    private ProductRepositoryInterface $productRepository;

    private SearchCriteriaBuilder $searchCriteriaBuilder;
    
    private LoggerInterface $logger;

    public function __construct(
        ConfigInterface $config,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LoggerInterface $logger
        )
    {
        $this->config = $config;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
    }

    /**
     * Resolve a product by its identifier attribute and value.
     *
     * @param string $value
     * @param string|null $identifierAttribute
     * @return ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function resolve(string $value, $identifierAttribute = null): ProductInterface
    {
        $attribute = $identifierAttribute ?? $this->config->getIdentifierAttribute();
        try {
            if ($attribute){
                $this->logger->debug(
                    __("Loading product with identifier attribute '%1' and value '%2'", $attribute, $value)
                );
                $this->searchCriteriaBuilder->addFilter($attribute, $value);
                
                $searchCriteria = $this->searchCriteriaBuilder->create();
                
                $searchResult = $this->productRepository->getList($searchCriteria);
                
                $items = $searchResult->getItems();
                if (empty($items)) {
                    throw new \Magento\Framework\Exception\NoSuchEntityException(__('Product not found.'));
                }
                
                return reset($items);
            }
        }
        catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->logger->error(
                __("Product with identifier attribute '%1' and value '%2' not found on Magento", $attribute, $value)
            );
        }
        // Fallback to default behavior if no identifier attribute is set
        $this->logger->debug(
            __("Loading product with SKU '%1'", $value)
        );
        return $this->productRepository->get($value);
        
    }

    public function resolveBySku(string $sku): ProductInterface
    {
        return $this->resolve($sku, 'sku');
    }
}