<?php

declare(strict_types=1);

namespace Gubee\Integration\Controller\Adminhtml\Oauth;

use Gubee\Integration\Service\Gubee\OAuth;
use Gubee\Integration\Service\Gubee\OAuthException;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Throwable;

use function __;

class Connect extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Gubee_Integration::config_gubee_integration';
    private OAuth $oauth;

    public function __construct(Context $context, OAuth $oauth)
    {
        parent::__construct($context);
        $this->oauth = $oauth;
    }

    public function execute(): ResultInterface
    {
        try {
            $authorization = $this->oauth->beginAuthorization(
                $this->getUrl('gubee/oauth/callback', ['_nosecret' => true, '_secure' => true]),
                (int) $this->_auth->getUser()->getId()
            );
            $this->_session->setData('gubee_oauth_attempt', $authorization['attempt']);
            return $this->resultRedirectFactory->create()->setUrl($authorization['url']);
        } catch (OAuthException $error) {
            $this->messageManager->addErrorMessage($error->getMessage());
        } catch (Throwable $error) {
            $this->messageManager->addErrorMessage(
                __('Unable to connect Gubee. Please try again or contact your administrator.')
            );
        }
        return $this->resultRedirectFactory->create()
            ->setPath('adminhtml/system_config/edit', ['section' => 'gubee']);
    }
}
