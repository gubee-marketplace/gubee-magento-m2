<?php

namespace Gubee\Integration\Model\Source\System\Config\Inventory;

use Magento\Framework\Data\OptionSourceInterface;

class Stock implements OptionSourceInterface
{
    /**
     * @var \Magento\InventoryApi\Api\StockRepositoryInterface
     */
    protected $stockRepository;
    public function __construct(
        \Magento\InventoryApi\Api\StockRepositoryInterface $stockRepository

    ) {
        $this->stockRepository = $stockRepository;
    }

    public function toOptionArray()
    {
        $stocks = $this->stockRepository->getList()->getItems();
        foreach ($stocks as $stock)
        {
            yield [
                'value' => $stock->getId(),
                'label' => $stock->getName()
            ];
        }
        
    }
}