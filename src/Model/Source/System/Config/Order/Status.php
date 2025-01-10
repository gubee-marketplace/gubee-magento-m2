<?php

namespace Gubee\Integration\Model\Source\System\Config\Order;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;

class Status implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    private $statusCollectionFactory;

    /**
     * Constructor
     *
     * @param CollectionFactory $statusCollectionFactory
     */
    public function __construct(CollectionFactory $statusCollectionFactory)
    {
        $this->statusCollectionFactory = $statusCollectionFactory;
    }

    /**
     * Retrieve list of order statuses
     *
     * @return array
     */
    public function toOptionArray()
    {
        $statuses = [];
        $collection = $this->statusCollectionFactory->create();

        foreach ($collection as $status) {
            $statuses[] = [
                'value' => $status->getStatus(),
                'label' => $status->getLabel()
            ];
        }

        return $statuses;
    }
}
