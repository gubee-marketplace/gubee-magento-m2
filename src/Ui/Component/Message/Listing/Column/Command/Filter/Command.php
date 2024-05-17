<?php

declare(strict_types=1);

namespace Gubee\Integration\Ui\Component\Message\Listing\Column\Command\Filter;

use Gubee\Integration\Model\ResourceModel\Message\CollectionFactory;
use Magento\Framework\Option\ArrayInterface;

use function __;

class Command implements ArrayInterface
{
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    public function toOptionArray()
    {
        $result = [];
        foreach ($this->getOptions() as $value => $label) {
            $result[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return $result;
    }

    public function getOptions()
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToSelect('command');
        $collection->getSelect()->group('command');
        $options = [];
        foreach ($collection as $item) {
            $options[$item->getCommand()] = __($item->getCommand());
        }
        return $options;
    }
}
