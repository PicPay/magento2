<?php

/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Picpay
 * @package     Picpay_Payment
 *
 */

declare(strict_types=1);

namespace Picpay\Payment\Api;

interface CallbackRepositoryInterface
{
    /**
     * Save Queue
     * @param \Picpay\Payment\Api\Data\CallbackInterface $callback
     * @return \Picpay\Payment\Api\Data\CallbackInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Picpay\Payment\Api\Data\CallbackInterface $callback
    );

    /**
     * Retrieve CallbackInterface
     * @param string $id
     * @return \Picpay\Payment\Api\Data\CallbackInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($id);

    /**
     * Retrieve Queue matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Picpay\Payment\Api\Data\CallbackSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );
}
