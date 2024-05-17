<?php

declare (strict_types = 1);

namespace Gubee\Integration\Controller\Adminhtml\Invoice;

use Exception;
use Gubee\Integration\Model\Invoice;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;

class Save extends Action {
    protected $dataPersistor;

    public function __construct(
        Context $context,
        DataPersistorInterface $dataPersistor
    ) {
        $this->dataPersistor = $dataPersistor;
        parent::__construct($context);
    }

    /**
     * Save action
     *
     * @return ResultInterface
     */
    public function execute() {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            $id = $this->getRequest()->getParam('invoice_id');

            $model = $this->_objectManager->create(Invoice::class)->load($id);
            if (!$model->getId() && $id) {
                $this->messageManager->addErrorMessage(__('This Invoice no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            }

            $model->setData($data);
            $orderId = $this->getRequest()->getParam('order_id');
            $model->setOrderId($orderId);
            try {
                $model->save();
                $this->messageManager->addSuccessMessage(__('You saved the Invoice.'));
                $this->dataPersistor->clear('gubee_integration_invoice');
                // check if is json
                if ($this->getRequest()->getParam('isAjax')) {
                    return $this->jsonResponse(
                        [
                            'success' => true,
                            'message' => __('You saved the Invoice.'),
                            'invoice_id' => $model->getId(),
                        ]
                    );
                }
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['invoice_id' => $model->getId()]);
                }
                return $resultRedirect->setPath(
                    'sales/order/view',
                    ['order_id' => $model->getOrderId()],
                );
            } catch (LocalizedException $e) {
                if ($this->getRequest()->getParam('isAjax')) {
                    // set http code to 400
                    $this->getResponse()->setHttpResponseCode(400);
                    return $this->jsonResponse(
                        [
                            'success' => false,
                            'message' => $e->getMessage(),
                        ]
                    );
                }
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (Exception $e) {
                if ($this->getRequest()->getParam('isAjax')) {
                    $this->getResponse()->setHttpResponseCode(400);
                    return $this->jsonResponse(
                        [
                            'success' => false,
                            'message' => $e->getMessage(),
                        ]
                    );
                }
                $this->messageManager->addExceptionMessage(
                    $e, __(
                        'Something went wrong while saving the Invoice, Error: %1',
                        $e->getMessage()
                    )
                );
            }

            $this->dataPersistor->set('gubee_integration_invoice', $data);
            return $resultRedirect->setPath('*/*/edit', ['invoice_id' => $this->getRequest()->getParam('invoice_id')]);
        }
        return $resultRedirect->setPath('sales/order/view', ['order_id' => $this->getRequest()->getParam('order_id')]);
    }

    /**
     * @param Invoice $model
     * @return ResultInterface
     */
    protected function jsonResponse(array $model) {
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($model);
        return $resultJson;
    }
}
