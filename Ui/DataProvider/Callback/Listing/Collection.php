<?php

namespace Picpay\Payment\Ui\DataProvider\Callback\Listing;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

class Collection extends SearchResult
{
    /**
     * Override _initSelect to add custom columns
     *
     * @return void
     */
    protected function _initSelect()
    {
        $this->addFilterToMap('entity_id', 'main_table.entity_id');
        $this->addOrder(
            'entity_id',
            self::SORT_ORDER_DESC
        );
        parent::_initSelect();
    }
}
