<?php

namespace Picpay\Payment\Block\Plugin\Adminhtml\Order;

use \Magento\Backend\Model\UrlInterface;

class View
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * View constructor.
     * @param UrlInterface $urlBuilder
     */
    public function __construct(UrlInterface $urlBuilder)
    {
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @param \Magento\Sales\Block\Adminhtml\Order\View $view
     */
    public function beforeSetLayout(\Magento\Sales\Block\Adminhtml\Order\View $view)
    {
        $message =__('Are you sure you want to Sync Picpay Transaction?');

        $url = $this->urlBuilder->getUrl(
            'picpay_payment/consult/index',
            ['order_id' => $view->getOrderId()]
        );

        $view->addButton(
            'picpay_sync',
            [
                'label' => __('Sync Picpay Transaction'),
                'class' => 'go',
                'onclick' => "confirmSetLocation('{$message}', '{$url}')"
            ]
        );
    }
}