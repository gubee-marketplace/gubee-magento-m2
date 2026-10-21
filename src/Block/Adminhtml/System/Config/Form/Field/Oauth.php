<?php

declare(strict_types=1);

namespace Gubee\Integration\Block\Adminhtml\System\Config\Form\Field;

use Gubee\Integration\Service\Gubee\OAuth as OAuthService;
use Gubee\Integration\Service\Gubee\OAuthException;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Helper\PostHelper;
use Throwable;

use function __;
use function json_decode;
use function json_encode;

class Oauth extends Field
{
    private OAuthService $oauth;
    private PostHelper $postHelper;

    public function __construct(Context $context, OAuthService $oauth, PostHelper $postHelper, array $data = [])
    {
        parent::__construct($context, $data);
        $this->oauth      = $oauth;
        $this->postHelper = $postHelper;
    }

    protected function _getElementHtml(AbstractElement $element) // phpcs:ignore
    {
        try {
            $status = $this->oauth->getStatus();
        } catch (OAuthException $error) {
            $status = $error->getMessage();
        } catch (Throwable $error) {
            $status = 'Unable to read the Gubee connection. Please contact your administrator.';
        }
        $callback = $this->getUrl('gubee/oauth/callback', ['_nosecret' => true, '_secure' => true]);
        $post     = json_encode([
            'Gubee_Integration/js/oauth-connect' => json_decode(
                $this->postHelper->getPostData($this->getUrl('gubee/oauth/connect')),
                true
            ),
        ]);
        return '<p>' . $this->escapeHtml(__($status)) . '</p>'
            . '<p>' . $this->escapeHtml(__('Register this exact callback URL in your Gubee app:')) . '<br>'
            . '<code>' . $this->escapeHtml($callback) . '</code></p>'
            . '<p>' . $this->escapeHtml(__('Save your client ID and secret before connecting.')) . '</p>'
            . '<a class="action-default" href="#" data-mage-init="' . $this->escapeHtmlAttr($post) . '">'
            . $this->escapeHtml(__('Connect / Reconnect with Gubee')) . '</a>';
    }
}
