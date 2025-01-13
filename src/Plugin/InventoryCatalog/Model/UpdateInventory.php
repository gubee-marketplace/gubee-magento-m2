<?php
declare(strict_types=1);

namespace Gubee\Integration\Plugin\InventoryCatalog\Model;

use Gubee\Integration\Plugin\AbstractInventoryPlugin;
use Magento\InventoryCatalog\Model\UpdateInventory as CatalogUpdateInventory;
use Magento\InventoryCatalog\Model\UpdateInventory\InventoryData;
use Gubee\Integration\Command\Catalog\Product\Stock\SendCommand as StockSendCommand;

class UpdateInventory extends AbstractInventoryPlugin 
{
    public function shouldExecute(): bool
    {
        return $this->config->getPluginInventoryCatalogUpdate();
    }

    public function afterExecute(CatalogUpdateInventory $instance, $result, InventoryData $data)
    {
        if (!$this->shouldExecute()) {
            return $this;
        }
        $skus = $data->getSkus();
        foreach ($skus as $sku)
        {
            try {
                $this->queueManagement->append(
                    StockSendCommand::class,
                    [
                        "sku" => $sku
                    ]
                );
            }
            catch (\Exception $err)
            {
                $this->logger->critical("Could not submit stock update for SKU:{$sku} to queue", ['exception' => $err]);
            }
        }
        return null;
    }
}