<?php
declare(strict_types=1);

namespace Gubee\Integration\Plugin\InventoryReservations\Model\ResourceModel;

use Gubee\Integration\Plugin\AbstractInventoryPlugin;
use Magento\InventoryReservations\Model\ResourceModel\SaveMultiple as SaveMultipleOriginal;
use Magento\InventoryReservationsApi\Model\ReservationInterface;
use Gubee\Integration\Command\Catalog\Product\Stock\SendCommand as StockSendCommand;

class SaveMultiple extends AbstractInventoryPlugin
{
    public function shouldExecute(): bool
    {
        return $this->config->getPluginInventoryReservations();
    }
    /**
     * @see \Magento\InventoryReservations\Model\ResourceModel\SaveMultiple
     * @param ReservationInterface[] $reservations
     */
    public function afterExecute(SaveMultipleOriginal $instance, $result, array $reservations)
    {
        if (!$this->shouldExecute()) {
            return $this;
        }
        foreach ($reservations as $reservation) {
            try {
                $this->queueManagement->append(
                    StockSendCommand::class,
                    [
                        "sku" => $reservation->getSku()
                    ]
                );
            }
            catch (\Exception $err)
            {
                $this->logger->critical("Could not submit stock update for SKU:{$reservation->getSku()} to queue", ['exception' => $err]);
            }
        }
        return null;
    }
}
