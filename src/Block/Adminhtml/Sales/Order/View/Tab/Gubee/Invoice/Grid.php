<?php

declare(strict_types=1);

namespace Gubee\Integration\Block\Adminhtml\Sales\Order\View\Tab\Gubee\Invoice;

use Gubee\Integration\Block\Adminhtml\Sales\Order\View\Tab\Gubee\Invoice\Renderer\Origin;
use Gubee\Integration\Model\Config;
use Gubee\Integration\Model\ResourceModel\Invoice\Collection;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order;

use function __;

class Grid extends Extended implements TabInterface
{
    protected function _construct()
    {
        parent::_construct();
        $this->setId('invoiceGrid');
        $this->setDefaultSort('invoice_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = ObjectManager::getInstance()
            ->create(Collection::class);
        $collection->addFieldToFilter('order_id', $this->getOrder()->getId());
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * @return $this
     */
    protected function _prepareColumns()
    {
        $columns = [
            'danfeLink' => [
                'header' => __('Danfe Link'),
                'type'   => 'text',
                'index'  => 'danfeLink',
            ],
            'danfeXml'  => [
                'header' => __('Danfe Xml'),
                'type'   => 'text',
                'index'  => 'danfeXml',
            ],
            'issueDate' => [
                'header' => __('Issue Date'),
                'type'   => 'text',
                'index'  => 'issueDate',
            ],
            'key'       => [
                'header' => __('Key'),
                'type'   => 'text',
                'index'  => 'key',
            ],
            'line'      => [
                'header' => __('Line'),
                'type'   => 'text',
                'index'  => 'line',
            ],
            'number'    => [
                'header' => __('Number'),
                'type'   => 'text',
                'index'  => 'number',
            ],
            'order_id'  => [
                'header' => __('order_id'),
                'type'   => 'text',
                'index'  => 'order_id',
            ],
            'origin'    => [
                'header'   => __('Source Origin'),
                'type'     => 'text',
                'index'    => 'origin',
                'renderer' => Origin::class,
            ],
        ];
        foreach ($columns as $key => $column) {
            $this->addColumn($key, $column);
        }

        // add edit button
        $this->addColumn(
            'edit',
            [
                'header'           => __('Edit'),
                'type'             => 'action',
                'getter'           => 'getId',
                'actions'          => [
                    [
                        'caption' => __('Edit'),
                        'field'   => 'invoice_id',
                        'url'     => [
                            'base'   => 'gubee/invoice/edit',
                            'params' => ['order_id' => $this->getOrder()->getId()],
                        ],
                    ],
                ],
                'filter'           => false,
                'sortable'         => false,
                'index'            => 'invoice_id',
                'header_css_class' => 'col-action',
                'column_css_class' => 'col-action',
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('gubee_invoice/invoice/grid', ['order_id' => $this->getOrder()->getId()]);
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return ObjectManager::getInstance()->get(Registry::class)->registry('current_order');
    }

    /**
     * @return string
     */
    public function getTabheader()
    {
        return __('Gubee \ DANFE \ List');
    }

    /**
     * @return string
     */
    public function getTabTitle()
    {
        return __('Gubee \ DANFE \ List');
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

        if ($this->getOrder()->getPayment()->getMethod() === 'gubee') {
            return false;
        }

        return true;
    }

    public function getTabLabel()
    {
        return __('Gubee \ DANFE');
    }
}
