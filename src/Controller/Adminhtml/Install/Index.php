<?php

declare(strict_types=1);

namespace Gubee\Integration\Controller\Adminhtml\Install;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

use function __;

class Index implements HttpGetActionInterface
{
    /** @var PageFactory */
    protected $resultPageFactory;

    /**
     * Constructor
     */
    public function __construct(PageFactory $resultPageFactory)
    {
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        // change title
        $page = $this->resultPageFactory->create();
        $page->getConfig()->getTitle()->set(
            __('Gubee \ Welcome to the Gubee Integration!')->__toString()
        );
        return $page;
    }
}
