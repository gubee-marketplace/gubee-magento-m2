<?php

declare(strict_types=1);

namespace Gubee\Integration\Controller\Adminhtml\Catalog\Product\MassSync;

use Gubee\Integration\Command\Catalog\Product\SendCommand;
use Magento\Framework\Phrase;

use function __;

class Product extends AbstractMassSync
{
    protected function getCommandClass(): string
    {
        return SendCommand::class;
    }

    protected function getSuccessMessage(int $count): Phrase
    {
        return __('A total of %1 product(s) have been queued for a full sync with Gubee.', $count);
    }
}
