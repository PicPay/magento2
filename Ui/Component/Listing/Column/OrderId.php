<?php

namespace Picpay\Payment\Ui\Component\Listing\Column;

use Magento\Sales\Api\Data\OrderInterface;
use Picpay\Payment\Helper\Data;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Backend\Model\UrlInterface;

class OrderId extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;
    /**
     * @var Data
     */
    protected $helper;

    /** @var OrderInterface  */
    protected $order;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        Data $helper,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->helper = $helper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                if ($item['increment_id']) {
                    $order = $this->helper->loadOrder($item['increment_id']);
                    $orderId = $order->getId();
                    $item[$fieldName] = sprintf(
                        '<a href="%s">%s</a>',
                        $this->getViewLink($orderId),
                        $item['increment_id']
                    );
                } else {
                    $item[$fieldName] = __('Not Available');
                }
            }
        }

        return $dataSource;
    }

    /**
     * @param $entityId
     * @return string
     */
    protected function getViewLink($entityId)
    {
        return $this->urlBuilder->getUrl(
            'sales/order/view',
            ['order_id' => $entityId]
        );
    }
}
