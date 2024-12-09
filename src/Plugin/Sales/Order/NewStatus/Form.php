<?php

namespace Gubee\Integration\Plugin\Sales\Order\NewStatus;

use Gubee\Integration\Model\Source\System\Config\Gubee\Order\Status as GubeeOrderStatus;
use Magento\Config\Model\Config\Source\YesnoFactory;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
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
    protected $_coreRegistry;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Constructor
     *
     * @param GubeeOrderStatus $gubeeOrderStatus
     */
    public function __construct(
        GubeeOrderStatus $gubeeOrderStatus,
        YesnoFactory $yesnoFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->gubeeOrderStatus = $gubeeOrderStatus;
        $this->yesnoFactory = $yesnoFactory;
        $this->_coreRegistry = $registry;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Add Gubee fields to the form
     *
     * @param \Magento\Sales\Block\Adminhtml\Order\Status\NewStatus\Form $subject
     * @param \Magento\Framework\Data\Form $form
     * @return \Magento\Framework\Data\Form
     */
    public function afterSetForm(
        \Magento\Sales\Block\Adminhtml\Order\Status\NewStatus\Form $subject,
        $form
    ) {
        $fieldset = $subject->getForm()->getElement('base_fieldset');
        $model = $this->_coreRegistry->registry('current_status');
        $orderStatusAllowedLinkToGubee = $this->scopeConfig->getValue(
            'gubee/order_status/whitelist_linker_status_enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $orderStatusAllowedLinkToGubee = explode(',', $orderStatusAllowedLinkToGubee);

        if (!in_array($model['status'], $orderStatusAllowedLinkToGubee)) {
            return $form;
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

        return $form;
    }

    /**
     * Check if there is status on Gubee
     * @return bool
     */
    private function thereIsStatusOnGubee()
    {
        $gubeeStatus = $this->gubeeOrderStatus->toOptionArray();

        return count($gubeeStatus) > 1;
    }
}
