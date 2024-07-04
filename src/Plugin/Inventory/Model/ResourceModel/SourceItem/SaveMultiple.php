<?php
declare(strict_types=1);

namespace Gubee\Integration\Plugin\Inventory\Model\ResourceModel\SourceItem;

use Gubee\Integration\Command\Catalog\Product\Stock\SendCommand as StockSendCommand;
use Gubee\Integration\Plugin\AbstractInventoryPlugin;

class SaveMultiple extends AbstractInventoryPlugin
{
    public function shouldExecute(): bool
    {
        return $this->config->getPluginInventory();
    }
    /**
     * Fetches 
     * @see \Magento\Inventory\Model\ResourceModel\SourceItem\SaveMultiple
     */
    public function afterExecute(\Magento\Inventory\Model\ResourceModel\SourceItem\SaveMultiple $executor, $result, $sourceItems) 
    {
        if (!$this->shouldExecute()) {
            return $this;
        }
        foreach ($sourceItems as $item)
        {
            try {
                $this->queueManagement->append(
                    StockSendCommand::class,
                    [
                        "sku" => $item->getSku()
                    ]
                );
            }
            catch (\Exception $err)
            {
                $this->logger->critical("Could not submit stock update for SKU:{$item->getSku()} to queue", ['exception' => $err]);
            }
        }

        return $sourceItems;
    }
}