<?php

declare(strict_types=1);

namespace Gubee\Integration\Model\ResourceModel\Invoice;

use Gubee\Integration\Model\Invoice;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /** @inheritDoc */
    protected $_idFieldName = 'invoice_id';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            Invoice::class,
            \Gubee\Integration\Model\ResourceModel\Invoice::class
        );
    }
}
