<?php

declare(strict_types=1);

namespace Gubee\Integration\Controller\Adminhtml\Catalog\Product\MassSync;

use Gubee\Integration\Model\Queue\Management;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Phrase;
use Magento\Ui\Component\MassAction\Filter;

abstract class AbstractMassSync extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Gubee_Integration::Sync';

    protected Filter $filter;
    protected CollectionFactory $collectionFactory;
    protected Management $queueManagement;

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        Management $queueManagement
    ) {
        $this->filter            = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->queueManagement   = $queueManagement;
        parent::__construct($context);
    }

    abstract protected function getCommandClass(): string;

    abstract protected function getSuccessMessage(int $count): Phrase;

    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());

        foreach ($collection as $product) {
            $this->queueManagement->append(
                $this->getCommandClass(),
                ['sku' => $product->getSku()],
                (int) $product->getId()
            );
        }

        $this->messageManager->addSuccessMessage($this->getSuccessMessage($collection->getSize()));

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('catalog/product/index');
    }
}
