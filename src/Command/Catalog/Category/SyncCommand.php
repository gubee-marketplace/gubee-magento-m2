<?php

declare(strict_types=1);

namespace Gubee\Integration\Command\Catalog\Category;

use Gubee\Integration\Command\AbstractCommand;
use Gubee\SDK\Model\Catalog\Category;
use Gubee\SDK\Resource\Catalog\CategoryResource;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Exception\LogicException;

class SyncCommand extends AbstractCommand
{
    protected ObjectManagerInterface $objectManager;
    protected Collection $collection;
    protected CategoryResource $categoryResource;
    protected CategoryRepositoryInterface $categoryRepository;
    /**
     * @param string|null $name The name of the command; passing null means it must be set in configure()
     * @throws LogicException When the command name is empty.
     */
    public function __construct(
        ManagerInterface $eventDispatcher,
        LoggerInterface $logger,
        CategoryResource $categoryResource,
        ObjectManagerInterface $objectManager,
        CategoryRepositoryInterface $categoryRepository,
        CollectionFactory $categoryCollectionFactory
    ) {
        parent::__construct($eventDispatcher, $logger, "catalog:category:sync");
        $this->categoryResource   = $categoryResource;
        $this->collection         = $categoryCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('entity_id', ['gt' => 0]);
        $this->objectManager      = $objectManager;
        $this->categoryRepository = $categoryRepository;
    }

    protected function configure()
    {
        $this->setDescription("Sync the categories with Gubee");
    }

    protected function doExecute(): int
    {
        $categories = [];
        foreach ($this->collection as $category) {
            $categories[] = $this->objectManager->create(
                Category::class,
                [
                    'id'          => $category->getId(),
                    'name'        => $category->getName() ?: 'Unnamed category',
                    'description' => $category->getDescription() ?: '',
                    'is_active'   => $category->getIsActive(),
                    'parent'      => $category->getParentId() > 0 ? (int) $category->getParentId() : null,
                ]
            );
        }
        $this->categoryResource->bulkUpdate($categories);
        return 0;
    }

    public function getPriority(): int
    {
        return 1000;
    }
}
