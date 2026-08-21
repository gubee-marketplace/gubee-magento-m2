<?php

declare(strict_types=1);

namespace Gubee\Integration\Block\Adminhtml\Product\Edit\Button;

class SyncPrice extends AbstractSyncButton
{
    protected function getLabel(): string
    {
        return 'Sync Pricing';
    }

    protected function getRoute(): string
    {
        return 'gubee/catalog_product_sync/price';
    }

    protected function getSortOrder(): int
    {
        return 61;
    }
}
