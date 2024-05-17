<?php

declare(strict_types=1);

namespace Gubee\Integration\Block\Adminhtml\Message;

use Gubee\Integration\Api\Enum\Message\StatusEnum;
use Gubee\Integration\Model\ResourceModel\Message\CollectionFactory;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;

class Status extends Template
{
    protected CollectionFactory $collectionFactory;

    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
        $this->collectionFactory = $collectionFactory;
    }

    public function getPending()
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', ['eq' => StatusEnum::PENDING()->__toString()]);
        return $collection;
    }

    public function getProcessing()
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', ['eq' => StatusEnum::RUNNING()->__toString()]);
        return $collection;
    }

    public function getSuccess()
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', ['eq' => StatusEnum::DONE()->__toString()]);
        return $collection;
    }

    public function getError()
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', ['eq' => StatusEnum::ERROR()->__toString()]);
        return $collection;
    }

    public function getItems()
    {
        return $this->collectionFactory->create();
    }
}
