<?php

declare(strict_types=1);

namespace Gubee\Integration\Command\Catalog\Product\Price;

use Gubee\Integration\Api\Enum\Integration\StatusEnum;
use Gubee\Integration\Api\Data\ConfigInterface;
use Gubee\Integration\Command\AbstractCommand;
use Gubee\Integration\Helper\Catalog\Attribute;
use Gubee\Integration\Model\Catalog\Product\Identifier\Resolver;
use Gubee\Integration\Service\Model\Catalog\Product;
use Gubee\SDK\Resource\Catalog\ProductResource;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;

use function __;
use function sprintf;

class SendCommand extends AbstractCommand
{
    protected ProductRepositoryInterface $productRepository;
    protected ObjectManagerInterface $objectManager;
    protected Attribute $attribute;

    protected Resolver $resolver;

    protected ProductResource $productResource;

    public function __construct(
        ManagerInterface $eventDispatcher,
        LoggerInterface $logger,
        ConfigInterface $configManager,
        ProductRepositoryInterface $productRepository,
        ObjectManagerInterface $objectManager,
        Attribute $attribute,
        Resolver $resolver,
        ProductResource $productResource
    ) {
        parent::__construct($eventDispatcher, $logger, $configManager, "catalog:product:price:send");
        $this->productRepository = $productRepository;
        $this->objectManager     = $objectManager;
        $this->attribute         = $attribute;
        $this->resolver          = $resolver;
        $this->productResource   = $productResource;
    }

    protected function configure()
    {
        $this->setDescription("Send the price of a product to Gubee");
        $this->addArgument(
            'sku',
            InputArgument::REQUIRED,
            'The product SKU to send the price to Gubee'
        );
    }

    protected function doExecute(): int
    {
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
        if (! $this->shouldUpdate($product)) {
            $this->logger->error(
                __(
                    "The product with the SKU '%1' is not integrated with Gubee yet",
                    $this->input->getArgument('sku')
                )->__toString()
            );
            throw new \Exception(
                __(
                    "The product with the SKU '%1' is not integrated with Gubee yet",
                    $this->input->getArgument('sku')
                )->__toString()
            );
        }
        /**
         * @var Product $product
         */
        $product = $this->objectManager->create(
            Product::class,
            [
                'product' => $product,
                'lazyMode' => true
            ]
        );

        $product->savePrice();
        return 0;
    }

    public function getPriority(): int
    {
        return 700;
    }

    protected function shouldUpdate($product): bool
    {
        if (! $this->configManager->getValidateBySku()) {
            return $this->attribute->getRawAttributeValue(
                'gubee_integration_status',
                $product
            ) === StatusEnum::INTEGRATED()->__toString();
        }

        return true;
    }
}
