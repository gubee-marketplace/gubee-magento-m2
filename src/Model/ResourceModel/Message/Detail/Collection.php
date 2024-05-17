<?php

declare(strict_types=1);

namespace Gubee\Integration\Model\ResourceModel\Message\Detail;

use Gubee\Integration\Api\Data\Message\DetailInterface;
use Gubee\Integration\Model\Message\Detail;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /** @inheritDoc */
    protected $_idFieldName = DetailInterface::DETAIL_ID;

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            Detail::class,
            \Gubee\Integration\Model\ResourceModel\Message\Detail::class
        );
    }
}
