<?php

declare(strict_types=1);

namespace Gubee\Integration\Command\Catalog\Product;

use Gubee\Integration\Command\AbstractCommand;
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

    public function __construct(
        ManagerInterface $eventDispatcher,
        LoggerInterface $log,
        ProductRepositoryInterface $productRepository,
        ObjectManagerInterface $objectManager
    ) {
        parent::__construct($eventDispatcher, $log, "catalog:product:validate");
        $this->productRepository = $productRepository;
        $this->objectManager     = $objectManager;
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
        $product = $this->productRepository->get($this->input->getArgument('sku'));
        if (! $product->getId()) {
            $this->log->error(
                sprintf(
                    "%s",
                    __(
                        "The product with the SKU '%1' does not exist",
                        $this->input->getArgument('sku')
                    )->__toString()
                )
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
        $result = $product->validate();
        return 0;
    }
}
