<?php

declare(strict_types=1);

namespace Gubee\Integration\Model;

use Exception;
use Gubee\Integration\Api\Data\InvoiceInterface;
use Gubee\Integration\Api\Data\InvoiceInterfaceFactory;
use Gubee\Integration\Api\Data\InvoiceSearchResultsInterfaceFactory;
use Gubee\Integration\Api\InvoiceRepositoryInterface;
use Gubee\Integration\Model\ResourceModel\Invoice as ResourceInvoice;
use Gubee\Integration\Model\ResourceModel\Invoice\CollectionFactory as InvoiceCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

use function __;

class InvoiceRepository implements InvoiceRepositoryInterface
{
    /** @var Invoice */
    protected $searchResultsFactory;

    /** @var InvoiceInterfaceFactory */
    protected $invoiceFactory;

    /** @var CollectionProcessorInterface */
    protected $collectionProcessor;

    /** @var InvoiceCollectionFactory */
    protected $invoiceCollectionFactory;

    /** @var ResourceInvoice */
    protected $resource;

    public function __construct(
        ResourceInvoice $resource,
        InvoiceInterfaceFactory $invoiceFactory,
        InvoiceCollectionFactory $invoiceCollectionFactory,
        InvoiceSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource                 = $resource;
        $this->invoiceFactory           = $invoiceFactory;
        $this->invoiceCollectionFactory = $invoiceCollectionFactory;
        $this->searchResultsFactory     = $searchResultsFactory;
        $this->collectionProcessor      = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(InvoiceInterface $invoice)
    {
        try {
            $this->resource->save($invoice);
        } catch (Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the invoice: %1',
                $exception->getMessage()
            ));
        }
        return $invoice;
    }

    /**
     * @inheritDoc
     */
    public function get($invoiceId)
    {
        $invoice = $this->invoiceFactory->create();
        $this->resource->load($invoice, $invoiceId);
        if (! $invoice->getId()) {
            throw new NoSuchEntityException(__('Invoice with id "%1" does not exist.', $invoiceId));
        }
        return $invoice;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        SearchCriteriaInterface $criteria
    ) {
        $collection = $this->invoiceCollectionFactory->create();

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
    public function delete(InvoiceInterface $invoice)
    {
        try {
            $invoiceModel = $this->invoiceFactory->create();
            $this->resource->load($invoiceModel, $invoice->getInvoiceId());
            $this->resource->delete($invoiceModel);
        } catch (Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Invoice: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($invoiceId)
    {
        return $this->delete($this->get($invoiceId));
    }

    public function getByOrderId($orderId): InvoiceInterface
    {
        $invoice = $this->invoiceFactory->create();
        $this->resource->load($invoice, $orderId, 'order_id');
        return $invoice;
    }

    public function getByKey($key): InvoiceInterface
    {
        $invoice = $this->invoiceFactory->create();
        $this->resource->load($invoice, $key, 'key');
        return $invoice;
    }
}
