<?php

declare(strict_types=1);

namespace Gubee\Integration\Controller\Adminhtml\Invoice;

use Gubee\Integration\Controller\Adminhtml\Invoice;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

use function __;

class Edit extends Invoice
{
    protected $resultPageFactory;

    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context, $coreRegistry);
    }

    /**
     * Edit action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        // 1. Get ID and create model
        $id    = $this->getRequest()->getParam('invoice_id');
        $model = $this->_objectManager->create(\Gubee\Integration\Model\Invoice::class);

        // 2. Initial checking
        if ($id) {
            $model->load($id);
            if (! $model->getId()) {
                $this->messageManager->addErrorMessage(__('This Invoice no longer exists.'));
                /** @var Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }
        $this->_coreRegistry->register('gubee_integration_invoice', $model);

        // 3. Build edit form
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $this->initPage($resultPage)->addBreadcrumb(
            $id ? __('Edit Invoice') : __('New Invoice'),
            $id ? __('Edit Invoice') : __('New Invoice')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Invoices'));
        $resultPage->getConfig()->getTitle()->prepend($model->getId() ? __('Edit Invoice %1', $model->getId()) : __('New Invoice'));
        return $resultPage;
    }
}
