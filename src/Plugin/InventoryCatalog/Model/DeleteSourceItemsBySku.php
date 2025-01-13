<?php
declare(strict_types=1);

namespace Gubee\Integration\Plugin\InventoryCatalog\Model;

use Gubee\Integration\Plugin\AbstractInventoryPlugin;
use Magento\InventoryCatalog\Model\DeleteSourceItemsBySkus as CatalogDeleteSourceItemsBySkus;
use Magento\InventoryCatalog\Model\UpdateInventory\InventoryData;
use Gubee\Integration\Command\Catalog\Product\Stock\SendCommand as StockSendCommand;

class DeleteSourceItemsBySku extends AbstractInventoryPlugin 
{
    public function shouldExecute(): bool
    {
        return $this->config->getPluginInventoryCatalogDelete();
    }

    public function afterExecute(CatalogDeleteSourceItemsBySkus $instance, $result, array $skus)
    {
        if (!$this->shouldExecute()) {
            return $this;
        }
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