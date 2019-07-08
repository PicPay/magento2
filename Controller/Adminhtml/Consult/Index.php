<?php

namespace Picpay\Payment\Controller\Adminhtml\Consult;

use Magento\Framework\Controller\ResultFactory; 

class Index extends \Magento\Backend\App\Action
{
    /**
     * @var \Picpay\Payment\Helper\Data
     */
    protected $paymentHelper;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $salesOrderFactory;

    public function __construct(
        \Picpay\Payment\Helper\Data $paymentHelper,
        \Magento\Sales\Model\OrderFactory $salesOrderFactory
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->salesOrderFactory = $salesOrderFactory;
    }

    public function execute()
    {
        /** @var \Picpay\Payment\Helper\Data $helper */
        $helper = $this->paymentHelper;

        $orderId = $this->getRequest()->getParam("order_id");

        if(!$orderId) {
            $this->_redirectReferer();
            return;
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->salesOrderFactory->create()->load($orderId);

        if(!$order
            || !$order->getId()
            || $order->getPayment()->getMethodInstance()->getCode() != "picpay_standard"
        ) {
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            return $resultRedirect;
        }

        $return = $order->getPayment()->getMethodInstance()->consultRequest($order);

        if(!is_array($return) || $return['success'] == 0) {
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            return $resultRedirect;
        }

        $authorizationId = $order->getPayment()->getAdditionalInformation("authorizationId");

        $helper->updateOrder($order, $return, $authorizationId);

        $this->resultRedirectFactory->create()->setPath('adminhtml/sales_order/view', ['_current' => true, 'order_id' => $orderId]);
    }
}