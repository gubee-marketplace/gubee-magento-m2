<?php

declare(strict_types=1);

namespace Gubee\Integration\Model\ResourceModel\Message;

use Gubee\Integration\Api\Data\Message\DetailInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Detail extends AbstractDb
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            DetailInterface::TABLE,
            DetailInterface::DETAIL_ID
        );
    }
}
