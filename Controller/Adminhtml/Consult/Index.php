<?php

namespace Picpay\Payment\Controller\Adminhtml\Consult;

use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\Order;

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
        \Magento\Backend\App\Action\Context $context,
        \Picpay\Payment\Helper\Data $paymentHelper,
        \Magento\Sales\Model\OrderFactory $salesOrderFactory
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->salesOrderFactory = $salesOrderFactory;
        parent::__construct($context);
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
            $this->messageManager->addErrorMessage(_('Erro to Sync'));
            return $this->_redirect('sales/order/view', ['_current' => true, 'order_id' => $orderId]);
        }

        $return = $this->consultRequest($order);


        if(!is_array($return) || $return['success'] == 0) {
            $this->messageManager->addErrorMessage(_('Erro to Sync'));
            return $this->_redirect('sales/order/view', ['_current' => true, 'order_id' => $orderId]);
        }

        $authorizationId = $order->getPayment()->getAdditionalInformation("authorizationId");

        $helper->updateOrder($order, $return, $authorizationId);

        $this->messageManager->addSuccessMessage(__('Sync Successfully.'));

        return $this->_redirect('sales/order/view', ['_current' => true, 'order_id' => $orderId]);
    }

    /**
     * Consult transaction via API
     *
     * @param Order $order
     * @return bool|mixed|string
     */
    public function consultRequest($order)
    {
        $result = $this->paymentHelper->requestApi(
            $this->paymentHelper->getApiUrl("/payments/{$order->getIncrementId()}/status"),
            array(),
            "GET"
        );
        if(isset($result['success'])) {
            return $result;
        }
        return false;
    }
}