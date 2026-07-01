<?php

declare(strict_types=1);

namespace Gubee\Integration\Command\Catalog\Product\Stock;

use Gubee\Integration\Api\Enum\Integration\StatusEnum;
use Gubee\Integration\Api\Data\ConfigInterface;
use Gubee\Integration\Command\AbstractCommand;
use Gubee\Integration\Helper\Catalog\Attribute;
use Gubee\Integration\Model\Catalog\Product\Identifier\Resolver;
use Gubee\Integration\Service\Model\Catalog\Product;
use Gubee\SDK\Resource\Catalog\ProductResource;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;

use function __;
use function sprintf;

class SendCommand extends AbstractCommand
{
    protected ProductRepositoryInterface $productRepository;
    protected ObjectManagerInterface $objectManager;
    protected Attribute $attribute;
    protected Configurable $configurableType;

    protected SearchCriteriaBuilder $searchCriteriaBuilder;

    protected FilterBuilder $filterBuilder;

    protected Resolver $resolver;

    protected ProductResource $productResource;

    public function __construct(
        ManagerInterface $eventDispatcher,
        LoggerInterface $logger,
        ConfigInterface $configManager,
        ProductRepositoryInterface $productRepository,
        ObjectManagerInterface $objectManager,
        Configurable $configurableType,
        Attribute $attribute,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        Resolver $resolver,
        ProductResource $productResource
    ) {
        parent::__construct($eventDispatcher, $logger, $configManager, "catalog:product:stock:send");
        $this->productRepository = $productRepository;
        $this->objectManager     = $objectManager;
        $this->attribute         = $attribute;
        $this->resolver          = $resolver;
        $this->configurableType = $configurableType;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->productResource = $productResource;
    }

    protected function configure()
    {
        $this->setDescription("Send the stock of a product to Gubee");
        $this->addArgument(
            'sku',
            InputArgument::REQUIRED,
            'The product SKU to send the stock to Gubee'
        );
    }

    protected function doExecute(): int
    {

        /**
         * @var \Magento\Catalog\Api\Data\ProductInterface[] $productsToUpdate
         */
        $productsToUpdate = [];
        $product = $this->resolver->resolve($this->input->getArgument('sku'));
        if (! $product->getId()) {
            $this->logger->error(
                __(
                    "The product with the SKU '%1' does not exist",
                    $this->input->getArgument('sku')
                )->__toString()
            );
            return 1;
        }

        $parents = $this->configurableType->getParentIdsByChild($product->getId());
        if ( count($parents) == 0 ) { //  has any parents, lets update it
            $productsToUpdate[] = $product;
        }
        else {
            $filter = $this->filterBuilder->setField('entity_id')->setConditionType('in')->setValue($parents)->create();
            $searchCriteria = $this->searchCriteriaBuilder->addFilter($filter)->create();
            $result = $this->productRepository->getList(
                $searchCriteria
            );
            $productsToUpdate = array_merge($productsToUpdate, $result->getItems());// append parents to products to update
        }


        return $this->updateProducts($productsToUpdate);
    }

    public function getPriority(): int
    {
        return 700;
    }
    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface[] $products
     */
    private function updateProducts (array $products = []) : int
    {
        try {

            foreach ($products as $productMage)
            {
                if (!$this->shouldUpdate($productMage)) {
                    $this->logger->error(
                        __(
                            "The product with the SKU '%1' is not integrated with Gubee yet",
                            $productMage->getSku()
                        )->__toString()
                    );
                    continue; // lets keep going, since it is a recoverable error
                }
                /**
                 * @var Product $product
                 */
                $product = $this->objectManager->create(
                    Product::class,
                    [
                        'product' => $productMage,
                        'lazyMode' => true
                    ]
                );
                $product->saveStock();

            }
            return 0;
        }
        catch (\Exception $err)
        {

            $this->logger->error(
                __(
                    "The product with the SKU '%1' could not be saved, exception: %2",
                    $this->input->getArgument('sku'),
                    $err->getMessage()
                )->__toString()
            );
            return 1;
        }
    }

    protected function shouldUpdate($product) : bool
    {
        if (! $this->configManager->getValidateBySku()) {
            return $this->attribute->getRawAttributeValue(
                'gubee_integration_status',
                $product
            ) === StatusEnum::INTEGRATED()->__toString();
        }

        return $this->productResource->getBySku($product->getSku())->getId() !== null;
    }
}
