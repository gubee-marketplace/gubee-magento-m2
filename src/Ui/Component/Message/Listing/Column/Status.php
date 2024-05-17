<?php

declare(strict_types=1);

namespace Gubee\Integration\Ui\Component\Message\Listing\Column;

use Gubee\Integration\Api\Enum\Message\StatusEnum;
use Magento\Ui\Component\Listing\Columns\Column;

use function __;

class Status extends Column
{
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
                $status                       = $item['status'];
                $item[$this->getData('name')] = $this->getStatusHtml((int) $status);
            }
        }

        return $dataSource;
    }

    public function getStatusHtml(int $status): string
    {
        switch ($status) {
            case StatusEnum::PENDING()->__toString():
                $class = 'grid-severity-minor';
                $label = __('Pending');
                break;
            case StatusEnum::RUNNING()->__toString():
                $class = 'grid-severity-minor';
                $label = __('Running');
                break;
            case StatusEnum::DONE()->__toString():
                $class = 'grid-severity-notice';
                $label = __('Done');
                break;
            case StatusEnum::ERROR()->__toString():
                $class = 'grid-severity-critical';
                $label = __('Error');
                break;
            case StatusEnum::FINISHED()->__toString():
                $class = 'grid-severity-critical';
                $label = __('Finished');
                break;
            case StatusEnum::FAILED()->__toString():
                $class = 'grid-severity-critical';
                $label = __('Failed');
                break;
        }

        return <<<HTML
<span class="{$class}"><span>{$label}</span></span>
HTML;
    }
}
