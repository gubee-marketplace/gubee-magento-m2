<?php

declare(strict_types=1);

namespace Gubee\Integration\Controller\Adminhtml\Catalog\Product\Sync;

use Gubee\Integration\Command\Catalog\Product\Stock\SendCommand;
use Magento\Framework\Phrase;

use function __;

class Stock extends AbstractSync
{
    protected function getCommandClass(): string
    {
        return SendCommand::class;
    }

    protected function getSuccessMessage(): Phrase
    {
        return __('The product stock has been queued for sync with Gubee.');
    }
}
