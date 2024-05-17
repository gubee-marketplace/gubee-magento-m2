<?php

/**
 * Copyright © Gubee Invoice All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Gubee\Integration\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Registry;

use function __;

abstract class Invoice extends Action
{
    const ADMIN_RESOURCE = 'Gubee_Invoice::top_level';
    protected $_coreRegistry;

    public function __construct(
        Context $context,
        Registry $coreRegistry
    ) {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context);
    }

    /**
     * Init page
     *
     * @param Page $resultPage
     * @return Page
     */
    public function initPage($resultPage)
    {
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE)
            ->addBreadcrumb(__('Gubee'), __('Gubee'))
            ->addBreadcrumb(__('Invoice'), __('Invoice'));
        return $resultPage;
    }
}
