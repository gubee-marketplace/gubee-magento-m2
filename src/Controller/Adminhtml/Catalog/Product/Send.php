<?php

declare(strict_types=1);

namespace Gubee\Integration\Controller\Adminhtml\Catalog\Product;

use Exception;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Catalog\Controller\Adminhtml\Product\MassStatus;
use Magento\Catalog\Model\Product\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;

use function __;
use function count;

class Send extends MassStatus
{
    /**
     * Update product(s) status action
     *
     * @throws NotFoundException
     * @return Redirect
     */
    public function execute()
    {
        if (! $this->getRequest()->isPost()) {
            throw new NotFoundException(__('Page not found'));
        }

        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $productIds = $collection->getAllIds();
        $storeId    = (int) $this->getRequest()->getParam('store', 0);
        $status     = (int) $this->getRequest()->getParam('status');
        $filters    = (array) $this->getRequest()->getParam('filters', []);

        if (isset($filters['store_id'])) {
            $storeId = (int) $filters['store_id'];
        }

        try {
            $this->_validateMassStatus($productIds, $status);
            $this->_objectManager->get(Action::class)
                ->updateAttributes($productIds, ['gubee' => 1], $storeId);
            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) have been queue to send.', count($productIds))
            );
            $this->_productPriceIndexerProcessor->reindexList($productIds);
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addException($e, __('Something went wrong while queueing the product(s).'));
        }

        /**
         * @var Redirect $resultRedirect
         */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('catalog/product/index');
    }
}
