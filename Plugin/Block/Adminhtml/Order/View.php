<?php

namespace Picpay\Payment\Plugin\Block\Adminhtml\Order;

use \Magento\Backend\Model\UrlInterface;
use Magento\Sales\Block\Adminhtml\Order\View as MagentoView;
use Picpay\Payment\Model\Ui\ConfigProvider;

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
     * @param MagentoView $view
     */
    public function beforeSetLayout(MagentoView $view): void
    {
        if ($view->getOrder()->getPayment()->getMethod() == ConfigProvider::CODE) {
            $message = __('Are you sure you want to Sync Picpay Transaction?');
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
}
