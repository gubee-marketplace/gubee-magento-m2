<?php

namespace Gubee\Integration\Observer\Adminhtml\Sales\Order\NewStatus\Form;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Gubee\Integration\Model\Source\System\Config\Gubee\Order\Status as GubeeOrderStatus;
use Magento\Config\Model\Config\Source\YesnoFactory;

class AddInputs implements ObserverInterface
{
    /**
     * @var GubeeOrderStatus
     */
    private $gubeeOrderStatus;

    /**
     * @var YesnoFactory
     */
    private $yesnoFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Constructor
     *
     * @param GubeeOrderStatus $gubeeOrderStatus
     * @param YesnoFactory $yesnoFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        GubeeOrderStatus $gubeeOrderStatus,
        YesnoFactory $yesnoFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->gubeeOrderStatus = $gubeeOrderStatus;
        $this->yesnoFactory = $yesnoFactory;
        $this->coreRegistry = $coreRegistry;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $block = $observer->getData('block');
        if (!$block instanceof \Magento\Sales\Block\Adminhtml\Order\Status\NewStatus\Form) {
            return;
        }

        $form = $block->getForm();
        $fieldset = $form->getElement('base_fieldset');
        $model = $this->coreRegistry->registry('current_status');

        $orderStatusAllowedLinkToGubee = $this->scopeConfig->getValue(
            'gubee/order_status/whitelist_linker_status_enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $orderStatusAllowedLinkToGubee = explode(',', $orderStatusAllowedLinkToGubee);

        if (!in_array($model['status'], $orderStatusAllowedLinkToGubee)) {
            return;
        }

        if ($fieldset && $this->thereIsStatusOnGubee()) {
            $fieldset->addField(
                'linked_to_gubee',
                'select',
                [
                    'name' => 'linked_to_gubee',
                    'label' => __('Linked to Gubee'),
                    'required' => true,
                    'note' => __('Select Yes to enable linking with Gubee status.'),
                    'values' => $this->yesnoFactory->create()->toOptionArray(),
                    'value' => $model['linked_to_gubee'] ?? false
                ]
            );

            $fieldset->addField(
                'gubee_status',
                'select',
                [
                    'name' => 'gubee_status',
                    'label' => __('Gubee Status'),
                    'required' => true,
                    'values' => $this->gubeeOrderStatus->toOptionArray(),
                    'disabled' => isset($model['linked_to_gubee']) && $model['linked_to_gubee'] ? false : true,
                    'value' => $model['gubee_status'] ?? ''
                ]
            );
        }
    }

    /**
     * Check if there is status on Gubee
     *
     * @return bool
     */
    private function thereIsStatusOnGubee()
    {
        $gubeeStatus = $this->gubeeOrderStatus->toOptionArray();

        return count($gubeeStatus) > 1;
    }
}
