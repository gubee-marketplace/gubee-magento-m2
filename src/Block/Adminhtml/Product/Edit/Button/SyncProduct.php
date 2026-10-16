<?php

declare(strict_types=1);

namespace Gubee\Integration\Block\Adminhtml\Product\Edit\Button;

class SyncProduct extends AbstractSyncButton
{
    protected function getLabel(): string
    {
        return 'Sync Whole Product';
    }

    protected function getRoute(): string
    {
        return 'gubee/catalog_product_sync/product';
    }

    protected function getSortOrder(): int
    {
        return 62;
    }
}
