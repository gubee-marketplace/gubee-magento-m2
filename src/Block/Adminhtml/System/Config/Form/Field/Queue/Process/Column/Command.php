<?php

declare(strict_types=1);

namespace Gubee\Integration\Block\Adminhtml\System\Config\Form\Field\Queue\Process\Column;

use Gubee\Integration\Command\Sales\Order\Processor\CanceledCommand;
use Gubee\Integration\Command\Sales\Order\Processor\CreatedCommand;
use Gubee\Integration\Command\Sales\Order\Processor\DeliveredCommand;
use Gubee\Integration\Command\Sales\Order\Processor\InvoicedCommand;
use Gubee\Integration\Command\Sales\Order\Processor\PaidCommand;
use Gubee\Integration\Command\Sales\Order\Processor\PayedCommand;
use Gubee\Integration\Command\Sales\Order\Processor\RejectedCommand;
use Gubee\Integration\Command\Sales\Order\Processor\ShippedCommand;
use Magento\Framework\View\Element\Html\Select;

use function __;
use function addslashes;

class Command extends Select
{
    public function getOptions()
    {
        return [
            CanceledCommand::class  => __(CanceledCommand::class),
            CreatedCommand::class   => __(CreatedCommand::class),
            DeliveredCommand::class => __(DeliveredCommand::class),
            InvoicedCommand::class  => __(InvoicedCommand::class),
            PaidCommand::class      => __(PaidCommand::class),
            PayedCommand::class     => __(PayedCommand::class),
            RejectedCommand::class  => __(RejectedCommand::class),
            ShippedCommand::class   => __(ShippedCommand::class),
        ];
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml()
    {
        if (! $this->getOptions()) {
            foreach ($this->getOptions() as $option) {
                $this->addOption($option['value'], addslashes($option['label']));
            }
        }
        return parent::_toHtml();
    }
}
