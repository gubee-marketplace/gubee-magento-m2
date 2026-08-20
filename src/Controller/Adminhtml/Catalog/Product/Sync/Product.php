<?php

declare(strict_types=1);

namespace Gubee\Integration\Controller\Adminhtml\Catalog\Product\Sync;

use Gubee\Integration\Command\Catalog\Product\SendCommand;
use Magento\Framework\Phrase;

use function __;

class Product extends AbstractSync
{
    protected function getCommandClass(): string
    {
        return SendCommand::class;
    }

    protected function getSuccessMessage(): Phrase
    {
        return __('The product has been queued for a full sync with Gubee.');
    }
}
