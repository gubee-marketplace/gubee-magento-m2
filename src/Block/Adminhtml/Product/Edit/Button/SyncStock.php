<?php

declare(strict_types=1);

namespace Gubee\Integration\Block\Adminhtml\Product\Edit\Button;

class SyncStock extends AbstractSyncButton
{
    protected function getLabel(): string
    {
        return 'Sync Stock';
    }

    protected function getRoute(): string
    {
        return 'gubee/catalog_product_sync/stock';
    }

    protected function getSortOrder(): int
    {
        return 60;
    }
}
