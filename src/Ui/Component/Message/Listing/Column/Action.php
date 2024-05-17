<?php

declare(strict_types=1);

namespace Gubee\Integration\Ui\Component\Message\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

use function __;

class Action extends Column
{
    public const URL_PATH_EXECUTE = 'gubee/message/execute';

    protected UrlInterface $urlBuilder;

    /**
     * @param array<int|string, mixed> $components
     * @param array<int|string, mixed> $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source method
     *
     * @param array<int|string, mixed> $dataSource
     * @return array<int|string, mixed>
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['message_id'])) {
                    $name                   = $this->getData('name');
                    $item[$name]['execute'] = [
                        'href'  => $this->urlBuilder->getUrl(
                            static::URL_PATH_EXECUTE,
                            [
                                'message_id' => $item['message_id'],
                            ]
                        ),
                        'label' => __('Execute'),
                    ];
                }
            }
        }
        return $dataSource;
    }
}
