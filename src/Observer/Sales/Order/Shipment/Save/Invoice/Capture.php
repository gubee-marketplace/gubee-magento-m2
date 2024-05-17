<?php

declare(strict_types=1);

namespace Gubee\Integration\Observer\Sales\Order\Shipment\Save\Invoice;

use Gubee\Integration\Observer\Sales\Order\Shipment\Save\Before;
use Magento\Framework\DataObject;

use function md5;
use function trim;

class Capture extends Before
{
    protected function process(): void
    {
        // create a lock to avoid loop with registry
        if ($this->registry->registry('gubee_shipment_save_invoice_capture')) {
            return;
        }
        $shipment = $this->getObserver()->getShipment();
        $params   = new DataObject(
            $this->request->getParams()
        );
        $hashes   = [];
        if (is_iterable($params->getTracking())) {
            foreach ($params->getTracking() as $tracking) {
                $hashes[$this->hashTracking(
                    trim($tracking['title']),
                    trim($tracking['carrier_code']),
                    trim($tracking['number'])
                )] = $tracking['shipment_key'];
            }
            $this->registry->register('gubee_shipment_save_invoice_capture', true);
            foreach ($shipment->getTracks() as $tracking) {
                $hash = $this->hashTracking(
                    trim($tracking->getTitle()),
                    trim($tracking->getCarrierCode()),
                    trim($tracking->getNumber())
                );
                if (! isset($hashes[$hash])) {
                    continue;
                }
                $tracking->setShipmentId($hashes[$hash]);
                $tracking->save();
            }
        }
    }

    public function hashTracking(
        string $title,
        string $carrierCode,
        string $trackNumber
    ) {
        return md5($title . $carrierCode . $trackNumber);
    }
}
