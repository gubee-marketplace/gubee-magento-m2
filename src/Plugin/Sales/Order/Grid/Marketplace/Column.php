<?php

declare(strict_types=1);

namespace Gubee\Integration\Plugin\Sales\Order\Grid\Marketplace;

use Closure;
use Gubee\Integration\Model\Config;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as SalesOrderGridCollection;

class Column
{
    protected MessageManager $messageManager;
    protected SalesOrderGridCollection $collection;
    protected Config $config;

    public function __construct(
        Config $config,
        MessageManager $messageManager,
        SalesOrderGridCollection $collection
    ) {
        $this->config         = $config;
        $this->messageManager = $messageManager;
        $this->collection     = $collection;
    }

    public function aroundGetReport(
        CollectionFactory $subject,
        Closure $proceed,
        $requestName
    ) {
        $result = $proceed($requestName);
        if (!$this->config->getActive()) {
            return $result;
        }
        if ($requestName == 'sales_order_grid_data_source') {
            if ($result instanceof $this->collection) {
                $select = $result->getSelect();
                $select->joinLeft(
                    ["gubee_order" => "gubee_integration_order"],
                    'main_table.entity_id = gubee_order.order_id ',
                    ['gubee_marketplace', 'gubee_order_id']
                )->distinct();
            }
        }
        return $result;
    }
}
