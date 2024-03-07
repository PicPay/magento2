<?php
/**
 *
 *
 *
 *
 *
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Picpay
 * @package     Picpay_Payment
 *
 *
 */

declare(strict_types=1);

namespace Picpay\Payment\Api\Data;

interface CallbackSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get transaction list.
     * @return \Picpay\Payment\Api\Data\CallbackInterface[]
     */
    public function getItems();

    /**
     * Set entity_id list.
     * @param \Picpay\Payment\Api\Data\CallbackInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

