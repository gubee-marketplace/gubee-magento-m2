<?php

namespace Gubee\Integration\Plugin\Sales\Order\Status;

use Gubee\Integration\Model\Source\System\Config\General\Fullfilment;
use Magento\Config\Model\Config\Source\YesnoFactory;

class Gubee
{
    /**
     * @var Fullfilment
     */
    private $fullfilment;

    /**
     * @var YesnoFactory
     */
    private $yesnoFactory;

    /**
     * Constructor
     *
     * @param Fullfilment $fullfilment
     */
    public function __construct(
        Fullfilment $fullfilment,
        YesnoFactory $yesnoFactory

    ) {
        $this->fullfilment = $fullfilment;
        $this->yesnoFactory = $yesnoFactory;
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

        if ($fieldset) {
            $fieldset->addField(
                'link_to_gubee',
                'select',
                [
                    'name' => 'link_to_gubee',
                    'label' => __('Link to Gubee'),
                    'required' => true,
                    'note' => __('Select Yes to enable linking with Gubee status.'),
                    'values' => $this->yesnoFactory->create()->toOptionArray()
                ]
            );

            $fieldset->addField(
                'gubee_status',
                'select',
                [
                    'name' => 'gubee_status',
                    'label' => __('Gubee Status'),
                    'required' => true,
                    'note' => __('Link with Gubee status.'),
                    'values' => $this->fullfilment->toOptionArray(),
                    'disabled' => true
                ]
            );
        }

        return $form;
    }
}
