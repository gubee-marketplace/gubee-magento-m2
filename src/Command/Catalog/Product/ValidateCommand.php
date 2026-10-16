<?php

declare(strict_types=1);

namespace Gubee\Integration\Command\Catalog\Product;

use Gubee\Integration\Api\Data\ConfigInterface;
use Gubee\Integration\Command\AbstractCommand;
use Gubee\Integration\Model\Catalog\Product\Identifier\Resolver;
use Gubee\Integration\Service\Model\Catalog\Product;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;

use function __;
use function sprintf;

class ValidateCommand extends AbstractCommand
{
    protected ProductRepositoryInterface $productRepository;
    protected ObjectManagerInterface $objectManager;
    protected Resolver $resolver;

    public function __construct(
        ManagerInterface $eventDispatcher,
        LoggerInterface $log,
        ConfigInterface $configManager,
        ProductRepositoryInterface $productRepository,
        ObjectManagerInterface $objectManager,
        Resolver $resolver
    ) {
        parent::__construct($eventDispatcher, $log, $configManager, "catalog:product:validate");
        $this->productRepository = $productRepository;
        $this->objectManager     = $objectManager;
        $this->resolver          = $resolver;
    }

    protected function configure()
    {
        $this->setDescription("Validate the product");
        $this->setHelp("This command will validate the product");
        $this->addArgument(
            'sku',
            InputArgument::REQUIRED,
            'The product SKU to be validated'
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

        $product = $this->objectManager->create(
            Product::class,
            [
                'product' => $product,
            ]
        );

        $this->eventDispatcher->dispatch(
            'gubee_catalog_product_validate',
            [
                'product' => $product,
            ]
        );
        $product->validate();
        return 0;
    }
}
