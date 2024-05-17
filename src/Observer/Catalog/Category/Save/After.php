<?php

declare(strict_types=1);

namespace Gubee\Integration\Observer\Catalog\Category\Save;

use Gubee\Integration\Command\Catalog\Category\SyncCommand;
use Gubee\Integration\Observer\AbstractObserver;

class After extends AbstractObserver
{
    protected function process(): void
    {
        $this->queueManagement->append(
            SyncCommand::class,
            []
        );
    }
}
