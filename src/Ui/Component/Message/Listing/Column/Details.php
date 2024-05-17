<?php

declare(strict_types=1);

namespace Gubee\Integration\Ui\Component\Message\Listing\Column;

use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

use function sprintf;

class Details extends Column
{
    public const URL_PATH_DETAILS = 'gubee/message_detail/index';

    protected UrlInterface $urlBuilder;
    protected FormKey $formKey;

    /**
     * @param array<int|string, mixed> $components
     * @param array<int|string, mixed> $data
     */
    public function __construct(
        FormKey $formKey,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->formKey    = $formKey;
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare status column
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $messageId                    = $item['message_id'];
                $item[$this->getData('name')] = $this->getDetails($messageId);
            }
        }

        return $dataSource;
    }

    public function getDetails($messageId)
    {
        return sprintf(
            "<button onclick='window.messageDetails.showDetails(this, \"%s\", \"%s\")'>Show Details</button>",
            $messageId,
            $this->urlBuilder->getUrl(
                self::URL_PATH_DETAILS,
                [
                    'message_id' => $messageId,
                ]
            ) . '?isAjax=true&form_key=' . $this->formKey->getFormKey()
        );
    }
}
