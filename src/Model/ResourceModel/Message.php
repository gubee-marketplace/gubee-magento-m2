<?php

declare(strict_types=1);

namespace Gubee\Integration\Model\ResourceModel;

use Gubee\Integration\Api\Data\MessageInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Message extends AbstractDb
{
    /**
     * @inheritDoc
     */
    // phpcs:disable
    protected function _construct()
    {
        $this->_init(MessageInterface::TABLE, MessageInterface::MESSAGE_ID);
    }
// phpcs:enable
}
