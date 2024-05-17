<?php

declare(strict_types=1);

namespace Gubee\Integration\Command\Sales\Order\Processor;

class RejectedCommand extends CanceledCommand
{
    protected function doExecute() : int
    {
        return 0; //do not cancel the order if rejected
    }
}
