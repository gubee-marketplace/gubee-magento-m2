<?php

declare(strict_types=1);

namespace Gubee\Integration\Controller\Adminhtml\Message\Order\Unsuccessful;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

use function __;

class Index extends Action
{
    protected PageFactory $resultPageFactory;

    /**
     * Constructor
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Index action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(
            __("Unsuccessful Orders Integration")->__toString()
        );
        return $resultPage;
    }
}
