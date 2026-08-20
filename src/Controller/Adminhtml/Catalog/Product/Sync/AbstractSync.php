<?php

declare(strict_types=1);

namespace Gubee\Integration\Controller\Adminhtml\Catalog\Product\Sync;

use Gubee\Integration\Model\Queue\Management;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;

use function __;

abstract class AbstractSync extends Action
{
    const ADMIN_RESOURCE = 'Gubee_Integration::Sync';

    protected ProductRepositoryInterface $productRepository;
    protected Management $queueManagement;

    public function __construct(
        Context $context,
        ProductRepositoryInterface $productRepository,
        Management $queueManagement
    ) {
        $this->productRepository = $productRepository;
        $this->queueManagement   = $queueManagement;
        parent::__construct($context);
    }

    abstract protected function getCommandClass(): string;

    abstract protected function getSuccessMessage(): Phrase;

    public function execute()
    {
        $id = (int) $this->getRequest()->getParam('id');

        try {
            $product = $this->productRepository->getById($id);
            $this->queueManagement->append(
                $this->getCommandClass(),
                ['sku' => $product->getSku()],
                (int) $product->getId()
            );
            $this->messageManager->addSuccessMessage($this->getSuccessMessage());
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('The product no longer exists.'));
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('catalog/product/edit', ['id' => $id]);
    }
}
