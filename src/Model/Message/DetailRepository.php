<?php

declare(strict_types=1);

namespace Gubee\Integration\Model\Message;

use Exception;
use Gubee\Integration\Api\Data\Message\DetailInterface;
use Gubee\Integration\Api\Data\Message\DetailInterfaceFactory;
use Gubee\Integration\Api\Data\Message\DetailSearchResultsInterfaceFactory;
use Gubee\Integration\Api\Message\DetailRepositoryInterface;
use Gubee\Integration\Model\ResourceModel\Message\Detail as ResourceDetail;
use Gubee\Integration\Model\ResourceModel\Message\Detail\CollectionFactory as DetailCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

use function __;

class DetailRepository implements DetailRepositoryInterface
{
    /** @var ResourceDetail */
    protected $resource;

    /** @var DetailInterfaceFactory */
    protected $detailFactory;

    /** @var DetailCollectionFactory */
    protected $detailCollectionFactory;

    /** @var Detail */
    protected $searchResultsFactory;

    /** @var CollectionProcessorInterface */
    protected $collectionProcessor;

    public function __construct(
        ResourceDetail $resource,
        DetailInterfaceFactory $detailFactory,
        DetailCollectionFactory $detailCollectionFactory,
        DetailSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource                = $resource;
        $this->detailFactory           = $detailFactory;
        $this->detailCollectionFactory = $detailCollectionFactory;
        $this->searchResultsFactory    = $searchResultsFactory;
        $this->collectionProcessor     = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(DetailInterface $detail)
    {
        try {
            $this->resource->save($detail);
        } catch (Exception $exception) {
            throw new CouldNotSaveException(
                __(
                    'Could not save the detail: %1',
                    $exception->getMessage()
                )
            );
        }
        return $detail;
    }

    /**
     * @inheritDoc
     */
    public function get($detailId)
    {
        $detail = $this->detailFactory->create();
        $this->resource->load($detail, $detailId);
        if (! $detail->getId()) {
            throw new NoSuchEntityException(__('Detail with id "%1" does not exist.', $detailId));
        }
        return $detail;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        SearchCriteriaInterface $criteria
    ) {
        $collection = $this->detailCollectionFactory->create();

        $this->collectionProcessor->process($criteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

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
    public function delete(DetailInterface $detail)
    {
        try {
            $detailModel = $this->detailFactory->create();
            $this->resource->load($detailModel, $detail->getDetailId());
            $this->resource->delete($detailModel);
        } catch (Exception $exception) {
            throw new CouldNotDeleteException(
                __(
                    'Could not delete the Detail: %1',
                    $exception->getMessage()
                )
            );
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($detailId)
    {
        return $this->delete($this->get($detailId));
    }
}
