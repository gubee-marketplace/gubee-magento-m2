<?php

declare(strict_types=1);

namespace Gubee\Integration\Model\Source\System\Config\General;

use Gubee\SDK\Resource\PlatformResource;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Option\ArrayInterface;

class Fullfilment implements ArrayInterface
{
    protected PlatformResource $platformResource;

    public function toOptionArray()
    {
        $options   = [];
        try {
            $this->platformResource = ObjectManager::getInstance()->get(PlatformResource::class);
            $platforms = $this->platformResource->configuration();
            foreach ($platforms as $platform) {
                $options[] = [
                    'value' => $platform['code'],
                    'label' => $platform['label'],
                ];
            }
        } catch (\Exception $e) {
            $options[] = [
                'value' => '',
                'label' => __('Error loading platforms'),
            ];
        }
        return $options;
    }
}
