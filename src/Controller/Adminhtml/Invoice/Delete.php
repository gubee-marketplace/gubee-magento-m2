<?php

declare(strict_types=1);

namespace Gubee\Integration\Controller\Adminhtml\Invoice;

use Exception;
use Gubee\Integration\Controller\Adminhtml\Invoice;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;

use function __;

class Delete extends Invoice
{
    /**
     * Delete action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        // check if we know what should be deleted
        $id = $this->getRequest()->getParam('invoice_id');
        if ($id) {
            try {
                // init model and delete
                $model = $this->_objectManager->create(\Gubee\Integration\Model\Invoice::class);
                $model->load($id);
                $model->delete();
                // display success message
                $this->messageManager->addSuccessMessage(__('You deleted the Invoice.'));
                // go to grid
                return $resultRedirect->setPath('*/*/');
            } catch (Exception $e) {
                // display error message
                $this->messageManager->addErrorMessage($e->getMessage());
                // go back to edit form
                return $resultRedirect->setPath('*/*/edit', ['invoice_id' => $id]);
            }
        }
        // display error message
        $this->messageManager->addErrorMessage(__('We can\'t find a Invoice to delete.'));
        // go to grid
        return $resultRedirect->setPath('*/*/');
    }
}
