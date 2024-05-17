<?php

declare(strict_types=1);

namespace Gubee\Integration\Observer\Catalog\Product\Save;

use Gubee\Integration\Api\Enum\Integration\StatusEnum;
use Gubee\Integration\Command\Catalog\Product\SendCommand;
use Gubee\Integration\Helper\Catalog\Attribute;
use Gubee\Integration\Model\Config;
use Gubee\Integration\Model\Queue\Management;
use Gubee\Integration\Observer\Catalog\Product\AbstractProduct;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;

class After extends AbstractProduct
{
    protected Registry $registry;

    public function __construct(
        Config $config,
        LoggerInterface $logger,
        Management $queueManagement,
        Attribute $attribute,
        ProductRepositoryInterface $productRepository,
        ObjectManagerInterface $objectManager,
        Registry $registry
    )
    {
        parent::__construct(
            $config,
            $logger,
            $queueManagement,
            $attribute,
            $productRepository,
            $objectManager
        );
        $this->registry = $registry;
    }

    protected function process(): void
    {
        if ($message = $this->registry->registry('gubee_current_message')) {
            if ($message->getCommand() == SendCommand::class) {
                return;
            }
        }
        if (
            $this->attribute->getRawAttributeValue('gubee', $this->getProduct()) &&
            (
                $this->attribute->getRawAttributeValue(
                    'gubee_integration_status', $this->getProduct
                    ()
                )
                !== StatusEnum::INTEGRATED()->__toString()
                ||
                $this->attribute->getRawAttributeValue('gubee_sync', $this->getProduct())
            )
        ) {
            $this->queueManagement->append(
                SendCommand::class,
                [
                    'sku' => $this->getProduct()->getSku(),
                ],
                (int) $this->getProduct()->getId()
            );
        }

        $parents = $this->objectManager->create(
            Configurable::class
        )->getParentIdsByChild(
                $this->getProduct()->getId()
            );

        if (!empty($parents)) {

            foreach ($parents as $parentId) {
                $parentProduct = $this->productRepository->getById($parentId);
                if (
                    $this->attribute->getRawAttributeValue('gubee', $parentProduct) &&
                    (
                        $this->attribute->getRawAttributeValue(
                            'gubee_integration_status',
                            $parentProduct
                        )
                        !== StatusEnum::INTEGRATED()->__toString()
                        ||
                        $this->attribute->getRawAttributeValue('gubee_sync', $parentProduct)
                    )
                ) {
                    $this->queueManagement->append(
                        SendCommand::class,
                        [
                            'sku' => $this->productRepository->getById($parentId)->getSku(),
                        ],
                        (int) $parentId
                    );
                }

            }
        }
    }

    /**
     * Validate if the observer is allowed to run
     */
    protected function isAllowed(): bool
    {
        if (!$product = $this->getProduct()) {
            return false;
        }

        if (!$this->getConfig()->getActive()) {
            return false;
        }

        return true;
    }
}
