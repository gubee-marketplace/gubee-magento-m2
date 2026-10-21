<?php

declare(strict_types=1);

namespace Gubee\Integration\Controller\Adminhtml\Oauth;

use Gubee\Integration\Service\Gubee\OAuth;
use Gubee\Integration\Service\Gubee\OAuthException;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Throwable;

use function __;
use function is_array;
use function is_string;

class Callback extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Gubee_Integration::config_gubee_integration';

    // Gubee requires a fixed redirect URI; state and the admin session protect this callback.
    /** @var string[] */
    protected $_publicActions = ['callback']; // phpcs:ignore
    private OAuth $oauth;

    public function __construct(Context $context, OAuth $oauth)
    {
        parent::__construct($context);
        $this->oauth = $oauth;
    }

    public function execute(): ResultInterface
    {
        $attempt = $this->_session->getData('gubee_oauth_attempt', true);
        $this->getResponse()->setHeader('Referrer-Policy', 'no-referrer', true);
        $this->getResponse()->setHeader('Cache-Control', 'no-store', true);
        try {
            $state = $this->getRequest()->getQuery('state');
            $code  = $this->getRequest()->getQuery('code');
            $this->oauth->completeAuthorization(
                is_array($attempt) ? $attempt : [],
                is_string($state) ? $state : '',
                $this->getRequest()->getQuery('error') === null && is_string($code) ? $code : '',
                (int) $this->_auth->getUser()->getId()
            );
            $this->messageManager->addSuccessMessage(__('Gubee connected with OAuth.'));
        } catch (OAuthException $error) {
            $this->messageManager->addErrorMessage($error->getMessage());
        } catch (Throwable $error) {
            $this->messageManager->addErrorMessage(
                __('Unable to connect Gubee. Please try again or contact your administrator.')
            );
        }
        return $this->resultRedirectFactory->create()->setPath('adminhtml/system_config/edit', ['section' => 'gubee']);
    }
}
