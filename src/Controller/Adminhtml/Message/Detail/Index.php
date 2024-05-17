<?php

declare(strict_types=1);

namespace Gubee\Integration\Controller\Adminhtml\Message\Detail;

use Gubee\Integration\Model\ResourceModel\Message\Detail\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class Index extends Action
{
    protected JsonFactory $resultPageFactory;
    protected CollectionFactory $detailCollectionFactory;
    /**
     * Constructor
     */
    public function __construct(
        CollectionFactory $detailCollectionFactory,
        Context $context,
        JsonFactory $resultPageFactory
    ) {
        $this->detailCollectionFactory = $detailCollectionFactory;
        $this->resultPageFactory       = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Response is a json with all details from a given message, the message id
     * is passed as a parameter in the request with page and sort order
     *
     * @return JsonFactory
     */
    public function execute()
    {
        if (! $this->getRequest()->getParam('message_id')) {
            return $this->resultPageFactory->create()->setData([]);
        }

        $details = $this->detailCollectionFactory->create();
        $details->addFieldToFilter(
            'message_id',
            $this->getRequest()->getParam('message_id')
        );

        $resultPage = $this->resultPageFactory->create();
        return $resultPage->setData($details->getData());
    }
}
