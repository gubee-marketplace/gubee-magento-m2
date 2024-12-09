<?php

declare(strict_types=1);

namespace Gubee\Integration\Model\Source\System\Config\Order;

use Gubee\SDK\Resource\PlatformResource;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Option\ArrayInterface;

class Status implements ArrayInterface
{
    protected PlatformResource $platformResource;

    public function toOptionArray()
    {
        $options   = [];

        try {
            $this->platformResource = ObjectManager::getInstance()->get(PlatformResource::class);
            $config = $this->platformResource->configuration();

            $magentoConfigKey = array_search('MAGENTO', array_column($config, 'code'));

            if (!isset($config[$magentoConfigKey])) {
                throw new \Exception('Magento config not found');
            }

            $orderStatus = $config[$magentoConfigKey]['orderStatus'];

            if (!is_array($orderStatus)) {
                throw new \Exception('Order status not found');
            }

            $options = [
                [
                    'value' => '',
                    'label' => __('-- Please Select --'),
                ],
            ];

            foreach ($orderStatus as $statusName => $isEnabled) {
                if (!$isEnabled) {
                    continue;
                }

                $options[] = [
                    'value' => $statusName,
                    'label' => $statusName,
                ];
            }
        } catch (\Exception $e) {
            $options[] = [
                'value' => '',
                'label' => __('Error on load order status'),
            ];
        }
        return $options;
    }
}
