<?php 

namespace Picpay\Payment\Block;
  
class Info extends \Magento\Payment\Block\Info
{
    protected $_order = null;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Picpay\Payment\Helper\Data
     */
    protected $paymentHelper;

    public function __construct(
        \Magento\Framework\Registry $registry,
        \Picpay\Payment\Helper\Data $paymentHelper
    ) {
        $this->registry = $registry;
        $this->paymentHelper = $paymentHelper;
    }

//    protected function _construct()
//    {
//        parent::_construct();
//        $this->setTemplate('picpay/info.phtml');
//    }

    /**
     * Get order object instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder() {
        if(!$this->_order) {
            $this->_order = $this->registry->registry('current_order');
            if (!$this->_order) {
                $info = $this->getInfo();
                if ($this->getInfo() instanceof \Magento\Sales\Model\Order\Payment) {
                    $this->_order = $this->getInfo()->getOrder();
                }
            }
        }
		return $this->_order;
    }

    public function getPaymentUrl()
    {
        $order = $this->getOrder();
        if(is_null($order)) {
            return "";
        }

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();

        return $payment->getAdditionalInformation("paymentUrl");
    }

    public function getCancellationId()
    {
        $order = $this->getOrder();
        if(is_null($order)) {
            return "";
        }

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();

        return $payment->getAdditionalInformation("cancellationId");
    }

    public function getAuthorizationId()
    {
        $order = $this->getOrder();
        if(is_null($order)) {
            return "";
        }

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();

        return $payment->getAdditionalInformation("authorizationId");
    }

    public function getQrcode()
    {
        if($paymentUrl = $this->getPaymentUrl()) {
            /** @var \Picpay\Payment\Helper\Data $picpayHelper */
            $picpayHelper = $this->paymentHelper;

            $imageSize = $picpayHelper->getQrcodeInfoWidth()
                ? $picpayHelper->getQrcodeInfoWidth()
                : $picpayHelper::DEFAULT_QRCODE_WIDTH
            ;

            return $picpayHelper->generateQrCode($paymentUrl, $imageSize);
        }
    }
}