<?php

declare(strict_types=1);

namespace Gubee\Integration\Model\ResourceModel\Message;

use Gubee\Integration\Api\Data\MessageInterface;
use Gubee\Integration\Model\Message;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    // phpcs:disable
    /** @inheritDoc */
    protected $_idFieldName = MessageInterface::MESSAGE_ID;

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            Message::class,
                \Gubee\Integration\Model\ResourceModel\Message::class
        );
    }
// phpcs:enable
}
