<?php

namespace Gubee\Integration\Observer\Adminhtml\Sales\Order\NewStatus;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

class CheckUnique implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $model = $observer->getEvent()->getObject();

        if ($model instanceof \Magento\Sales\Model\Order\Status) {
            $gubeeStatus = $model->getData('gubee_status');

            if ($gubeeStatus) {
                $existingStatus = $model->getCollection()
                    ->addFieldToFilter('gubee_status', $gubeeStatus)
                    ->addFieldToFilter('status', ['neq' => $model->getData('status')])
                    ->getFirstItem();

                if ($existingStatus->getId()) {
                    throw new LocalizedException(
                        __("Only one type of 'gubee_status' is allowed. The status '%1' is already configured.", $gubeeStatus)
                    );
                }
            }
        }
    }
}
