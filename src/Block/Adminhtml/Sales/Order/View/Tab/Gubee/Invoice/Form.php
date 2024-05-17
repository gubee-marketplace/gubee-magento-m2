<?php

declare(strict_types=1);

namespace Gubee\Integration\Block\Adminhtml\Sales\Order\View\Tab\Gubee\Invoice;

use Gubee\Integration\Model\Config;
use Magento\Backend\Block\Widget\Form\Element\Dependence;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\App\ObjectManager;

use function __;

class Form extends Generic implements TabInterface
{
    /**
     * Prepare form before rendering HTML
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('gubee_invoice_');

        $model    = $this->_coreRegistry->registry('current_order');
        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Gubee \ Invoice'), 'class' => 'fieldset-wide']
        );
        $fields   = [
            'danfeLink' => [
                'type'     => 'text',
                'name'     => 'danfeLink',
                'label'    => __('Danfe Link'),
                'title'    => __('Danfe Link'),
                'required' => false,
            ],
            'danfeXml'  => [
                'type'     => 'text',
                'name'     => 'danfeXml',
                'label'    => __('Danfe Xml'),
                'title'    => __('Danfe Xml'),
                'required' => false,
            ],
            'issueDate' => [
                'type'        => 'date',
                'class'       => 'validate-date required-entry',
                'name'        => 'issueDate',
                'required'    => true,
                'label'       => __('Date'),
                'title'       => __('Date'),
                'date_format' => 'yyyy-MM-dd',
                'time_format' => 'hh:mm:ss',
            ],
            'key'       => [
                'type'     => 'text',
                'name'     => 'key',
                'label'    => __('Key'),
                'title'    => __('Key'),
                'required' => true,
            ],
            'line'      => [
                'type'     => 'text',
                'name'     => 'line',
                'class'    => 'validate-digits',
                'label'    => __('Line'),
                'title'    => __('Line'),
                'required' => true,
            ],
            'number'    => [
                'type'     => 'text',
                'name'     => 'number',
                'label'    => __('Number'),
                'title'    => __('Number'),
                'required' => true,
            ],
            'origin'    => [
                'type'     => 'select',
                'options'  => [
                    '0' => __('Magento'),
                ],
                'name'     => 'origin',
                'label'    => __('Source origin'),
                'title'    => __('Source origin'),
                'required' => false,
                'disabled' => true,
            ],
        ];

        foreach ($fields as $key => $field) {
            $fieldset->addField(
                $key,
                $field['type'],
                [
                    'name'        => $field['name'],
                    'label'       => $field['label'],
                    'title'       => $field['title'],
                    'required'    => $field['required'] ?? false,
                    'class'       => $field['class'] ?? '',
                    'options'     => $field['options'] ?? [],
                    'disabled'    => $field['disabled'] ?? false,
                    'date_format' => $field['date_format'] ?? 'yyyy-MM-dd',
                    'time_format' => $field['time_format'] ?? 'hh:mm:ss',
                ]
            );
        }

        $form->setValues($model->getData());
        $this->setForm($form);
        $form->setUseContainer(true);
        //  set submit action
        $form->setAction(
            $this->getUrl(
                'gubee/invoice/save',
                [
                    'order_id' => $model->getId(),
                ]
            )
        );

        // Add AJAX submit button
        $this->setChild(
            'form_after',
            $this->getLayout()->createBlock(Dependence::class)
                ->addFieldMap('gubee_invoice_submit_button', 'submit_button')
                ->addFieldMap('gubee_invoice_form', 'form')
                ->addFieldDependence('form', 'submit_button', '1')
        );

        $submitButton = $fieldset->addField(
            'gubee_invoice_save',
            'note',
            [
                'text' => '<input type="submit" name="gubee_invoice_save" class="action-primary"/>',
            ]
        );

        $fieldset->addField('error_message', 'note', [
            'text' => '<div id="error_message" style="display:none"></div>',
        ]);

        $submitButton->setAfterElementHtml(<<<HTML
<script>
require([
    "jquery",
    "mage/mage"
],function($) {
    $(document).ready(function() {
        var form = $('input[type="submit"][name="gubee_invoice_save"]').closest('form');
        console.log(form)
        form.on('submit', function (event) {
            event.preventDefault();
        })
        $('button[name="gubee_invoice_save"]').on('click', function (event) {
            event.preventDefault();
        })
        $(form).mage(
            'validation',
            {
                submitHandler: function (form) {
                    // check validation
                    if (!$(form).valid()) {
                        $(form).reportValidity();
                        return false;
                    }
                    console.log($(form).attr('action'))
                    $.ajax({
                        url: $(form).attr('action'),
                        data: $(form).serialize(),
                        type: 'POST',
                        dataType: 'json',
                        showLoader: true,
                        success: function (response) {
                            if (response.error) {
                                $('#error_message').html(response.message);
                                $('#error_message').show();
                                // set timeout to hide error message
                                setTimeout(function () {
                                    $('#error_message').html('');
                                    $('#error_message').hide();
                                }, 5000);
                            } else {
                                $('#error_message').html(
                                    '<div class="message message-success success">' +
                                    '<div>' + response.message + '</div>' +
                                    '</div>'
                                );
                                // clear fields values
                                form.reset();

                                $('#error_message').show();
                                // set timeout to hide error message
                                setTimeout(function () {
                                    $('#error_message').html('');
                                    $('#error_message').hide();
                                }, 5000);

                            }

                        }
                    })
                }
            }
        );


    });

});
</script>
HTML
        );

        return $this;
    }

    public function getTabLabel()
    {
        return __('Gubee \ New DANFE');
    }

    public function getTabTitle()
    {
        return __('Gubee \ New DANFE');
    }

    /**
     * @return bool
     */
    public function canShowTab()
    {
        return ! $this->isHidden();
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        if (
            ObjectManager::getInstance()->get(Config::class)->getActive() === false
        ) {
            return false;
        }
        $order = $this->_coreRegistry->registry('current_order');
        if ($order->getPayment()->getMethod() === 'gubee') {
            return false;
        }

        return true;
    }
}
