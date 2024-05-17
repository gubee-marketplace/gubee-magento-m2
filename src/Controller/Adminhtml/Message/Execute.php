<?php

declare(strict_types=1);

namespace Gubee\Integration\Controller\Adminhtml\Message;

use Exception;
use Gubee\Integration\Api\MessageRepositoryInterface;
use Gubee\Integration\Model\Message\Management;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

use function __;

class Execute extends Action
{
    protected MessageRepositoryInterface $messageRepository;
    protected Management $management;

    public function __construct(
        MessageRepositoryInterface $messageRepository,
        Management $management,
        Context $context
    ) {
        $this->management        = $management;
        $this->messageRepository = $messageRepository;
        parent::__construct($context);
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        try {
            if (! isset($params['message_id'])) {
                throw new Exception('Id is required');
            }
            $message = $this->messageRepository->get($params['message_id']);
            if (! $message->getId()) {
                throw new Exception('Message not found');
            }
            $this->management->process($message);
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(
                __(
                    "An error occurred while processing the message: %1",
                    $e->getMessage()
                )->__toString()
            );
        } finally {
            return $this->_redirect('gubee/message/index');
        }
    }
}
