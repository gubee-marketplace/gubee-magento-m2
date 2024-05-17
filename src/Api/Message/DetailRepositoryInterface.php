<?php

declare(strict_types=1);

namespace Gubee\Integration\Api\Message;

use Gubee\Integration\Api\Data\Message\DetailInterface;
use Gubee\Integration\Api\Data\Message\DetailSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface DetailRepositoryInterface
{
    /**
     * Save Detail
     *
     * @return DetailInterface
     * @throws LocalizedException
     */
    public function save(
        DetailInterface $detail
    );

    /**
     * Retrieve Detail
     *
     * @param string $detailId
     * @return DetailInterface
     * @throws LocalizedException
     */
    public function get($detailId);

    /**
     * Retrieve Detail matching the specified criteria.
     *
     * @return DetailSearchResultsInterface
     * @throws LocalizedException
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete Detail
     *
     * @return bool true on success
     * @throws LocalizedException
     */
    public function delete(
        DetailInterface $detail
    );

    /**
     * Delete Detail by ID
     *
     * @param string $detailId
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById($detailId);
}
