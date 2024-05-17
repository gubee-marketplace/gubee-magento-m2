<?php

declare(strict_types=1);

namespace Gubee\Integration\Api;

use Gubee\Integration\Api\Data\MessageInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface MessageRepositoryInterface
{
    /**
     * Save Message
     *
     * @throws LocalizedException
     */
    public function save(
        MessageInterface $message
    ): MessageInterface;

    /**
     * Retrieve Message
     *
     * @param string $messageId
     * @throws LocalizedException
     */
    public function get($messageId): MessageInterface;

    /**
     * Retrieve Message matching the specified criteria.
     *
     * @throws LocalizedException
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete Message
     *
     * @return bool true on success
     * @throws LocalizedException
     */
    public function delete(
        MessageInterface $message
    ): bool;

    /**
     * Delete Message by ID
     *
     * @param string $messageId
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById($messageId): bool;
}
