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

namespace Picpay\Payment\Model\ResourceModel\Callback;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Define resource model.
     */
    protected function _construct()
    {
        $this->_init(
            \Picpay\Payment\Model\Callback::class,
            \Picpay\Payment\Model\ResourceModel\Callback::class
        );
    }
}
