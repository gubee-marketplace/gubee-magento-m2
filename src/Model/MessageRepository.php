<?php

declare(strict_types=1);

namespace Gubee\Integration\Model;

use Exception;
use Gubee\Integration\Api\Data\MessageInterface;
use Gubee\Integration\Api\Data\MessageInterfaceFactory;
use Gubee\Integration\Api\Data\MessageSearchResultsInterfaceFactory;
use Gubee\Integration\Api\MessageRepositoryInterface;
use Gubee\Integration\Model\ResourceModel\Message as ResourceMessage;
use Gubee\Integration\Model\ResourceModel\Message\CollectionFactory as MessageCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

use function __;

class MessageRepository implements MessageRepositoryInterface
{
    protected ResourceMessage $resource;
    protected MessageInterfaceFactory $messageFactory; /* @phpstan-ignore-line */
    protected MessageCollectionFactory $messageCollectionFactory; /* @phpstan-ignore-line */
    protected MessageSearchResultsInterfaceFactory $searchResultsFactory; /* @phpstan-ignore-line */
    protected CollectionProcessorInterface $collectionProcessor;

    public function __construct(
        ResourceMessage $resource,
        MessageInterfaceFactory $messageFactory, /* @phpstan-ignore-line */
        MessageCollectionFactory $messageCollectionFactory, /* @phpstan-ignore-line */
        MessageSearchResultsInterfaceFactory $searchResultsFactory, /* @phpstan-ignore-line */
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource                 = $resource;
        $this->messageFactory           = $messageFactory;
        $this->messageCollectionFactory = $messageCollectionFactory;
        $this->searchResultsFactory     = $searchResultsFactory;
        $this->collectionProcessor      = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(
        MessageInterface $message
    ): MessageInterface {
        try {
            $this->resource->save($message); /* @phpstan-ignore-line */
        } catch (Exception $exception) {
            throw new CouldNotSaveException(
                __(
                    'Could not save the message: %1',
                    $exception->getMessage()
                )
            );
        }
        return $message;
    }

    /**
     * @inheritDoc
     */
    public function get($messageId): MessageInterface
    {
        $message = $this->messageFactory->create(); /* @phpstan-ignore-line */
        $this->resource->load($message, $messageId);
        if (! $message->getId()) {
            throw new NoSuchEntityException(__('Message with id "%1" does not exist.', $messageId));
        }
        return $message;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria
    ) {
        $collection = $this->messageCollectionFactory->create(); /* @phpstan-ignore-line */

        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create(); /* @phpstan-ignore-line */
        $searchResults->setSearchCriteria($searchCriteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function delete(
        MessageInterface $message
    ): bool {
        try {
            $messageModel = $this->messageFactory->create(); /* @phpstan-ignore-line */
            $this->resource->load($messageModel, $message->getMessageId());
            $this->resource->delete($messageModel);
        } catch (Exception $exception) {
            throw new CouldNotDeleteException(
                __(
                    'Could not delete the Message: %1',
                    $exception->getMessage()
                )
            );
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($messageId): bool
    {
        return $this->delete($this->get($messageId));
    }
}
