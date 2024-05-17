<?php

declare(strict_types=1);

namespace Gubee\Integration\Ui\Component\Unsuccessful\Listing\Column;

use function __;

class Action extends \Gubee\Integration\Ui\Component\Message\Listing\Column\Action
{
    public const URL_PATH_ADD_TO_QUEUE = 'gubee/message/queue';

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
                    $name                        = $this->getData('name');
                    $item[$name]['execute']      = [
                        'href'  => $this->urlBuilder->getUrl(
                            self::URL_PATH_EXECUTE,
                            [
                                'message_id' => $item['message_id'],
                            ]
                        ),
                        'label' => __('Execute'),
                    ];
                    $item[$name]['add_to_queue'] = [
                        'href'  => $this->urlBuilder->getUrl(
                            static::URL_PATH_ADD_TO_QUEUE,
                            [
                                'message_id' => $item['message_id'],
                            ]
                        ),
                        'label' => __('Add to Queue to Retry'),
                    ];
                }
            }
        }
        return $dataSource;
    }
}
