<?php

declare(strict_types=1);

namespace Gubee\Integration\Command\Catalog\Product\Attribute;

use Gubee\Integration\Command\AbstractCommand;
use Gubee\Integration\Model\ResourceModel\Catalog\Product\Attribute\CollectionFactory;
use Gubee\Integration\Service\Model\Catalog\Product\Attribute;
use Gubee\SDK\Resource\Catalog\Product\AttributeResource;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Psr\Log\LoggerInterface;

class SyncCommand extends AbstractCommand
{
    protected ObjectManagerInterface $objectManager;
    protected AttributeResource $attributeResource;

    public function __construct(
        ManagerInterface $eventDispatcher,
        LoggerInterface $logger,
        ObjectManagerInterface $objectManager,
        AttributeResource $attributeResource,
        CollectionFactory $collectionFactory
    ) {
        $this->attributeResource   = $attributeResource;
        $this->attributeCollection = $collectionFactory->create();
        $this->objectManager       = $objectManager;
        parent::__construct(
            $eventDispatcher,
            $logger,
            "catalog:product:attribute:sync"
        );
    }

    protected function doExecute(): int
    {
        $this->logger->info("Syncing attributes");
        $attributes = [];
        foreach ($this->attributeCollection->getItems() as $attribute) {
            $attributes[] = $this->objectManager->create(
                Attribute::class,
                [
                    'attribute' => $attribute,
                ]
            )->getGubeeAttribute();
        }
        $this->attributeResource->bulkUpdate($attributes);
        return 0;
    }

    public function getPriority(): int
    {
        return 1000;
    }
}
