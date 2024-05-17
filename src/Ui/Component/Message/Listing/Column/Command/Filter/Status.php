<?php

declare(strict_types=1);

namespace Gubee\Integration\Ui\Component\Message\Listing\Column\Command\Filter;

use Gubee\Integration\Api\Enum\Message\StatusEnum;
use Magento\Framework\Option\ArrayInterface;

use function __;

class Status implements ArrayInterface
{
    public function toOptionArray()
    {
        $result = [];
        foreach ($this->getOptions() as $value => $label) {
            $result[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return $result;
    }

    public function getOptions(): array
    {
        return [
            (int) StatusEnum::PENDING()->__toString()  => __('Pending'),
            (int) StatusEnum::RUNNING()->__toString()  => __('Running'),
            (int) StatusEnum::DONE()->__toString()     => __('Done'),
            (int) StatusEnum::ERROR()->__toString()    => __('Error'),
            (int) StatusEnum::FINISHED()->__toString() => __('Finished'),
            (int) StatusEnum::FAILED()->__toString()   => __('Failed'),
        ];
    }
}
