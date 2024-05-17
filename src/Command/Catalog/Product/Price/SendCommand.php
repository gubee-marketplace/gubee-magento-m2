<?php

declare(strict_types=1);

namespace Gubee\Integration\Command\Catalog\Product\Price;

use Gubee\Integration\Api\Enum\Integration\StatusEnum;
use Gubee\Integration\Command\AbstractCommand;
use Gubee\Integration\Helper\Catalog\Attribute;
use Gubee\Integration\Service\Model\Catalog\Product;
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

    public function __construct(
        ManagerInterface $eventDispatcher,
        LoggerInterface $logger,
        ProductRepositoryInterface $productRepository,
        ObjectManagerInterface $objectManager,
        Attribute $attribute
    ) {
        parent::__construct($eventDispatcher, $logger, "catalog:product:price:send");
        $this->productRepository = $productRepository;
        $this->objectManager     = $objectManager;
        $this->attribute         = $attribute;
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
        $product = $this->productRepository->get($this->input->getArgument('sku'));
        if (! $product->getId()) {
            $this->logger->error(
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
        if (
            $this->attribute->getRawAttributeValue(
                'gubee_integration_status',
                $product
            ) !== StatusEnum::INTEGRATED()->__toString()
        ) {
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
            return 1;
        }
        /**
         * @var Product $product
         */
        $product = $this->objectManager->create(
            Product::class,
            [
                'product' => $product,
            ]
        );

        $product->savePrice();
        return 0;
    }

    public function getPriority(): int
    {
        return 700;
    }
}
