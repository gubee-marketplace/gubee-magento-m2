<?php
declare(strict_types=1);

namespace Gubee\Integration\Model\Source\System\Config\General;

class SyncEntities implements \Magento\Framework\Data\OptionSourceInterface
{
    public const SYNC_ENTITIES = [
        'product' => 'Product',
        'category' => 'Category',
        'order' => 'Order',
    ];

    public function toOptionArray(): array
    {
        $options = [];
        foreach (self::SYNC_ENTITIES as $value => $label) {
            $options[] = ['value' => $value, 'label' => __($label)];
        }
        return $options;
    }
}