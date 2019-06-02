<?php

namespace Picpay\Payment\Block;

use Magento\Framework\Phrase;
use Magento\Payment\Block\ConfigurableInfo;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Gateway\ConfigInterface;
use Picpay\Payment\Gateway\Response\FraudHandler;

class Info extends ConfigurableInfo
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
        Context $context,
        ConfigInterface $config,
        \Magento\Framework\Registry $registry,
        \Picpay\Payment\Helper\Data $paymentHelper,
        array $data = []
    )
    {
        parent::__construct($context, $config, $data);
        $this->registry = $registry;
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * Returns label
     *
     * @param string $field
     * @return string | Phrase
     */
    protected function getLabel($field)
    {
        return __($field);
    }

    /**
     * Returns value view
     *
     * @param string $field
     * @param string $value
     * @return string | Phrase
     */
    protected function getValueView($field, $value)
    {
        switch ($field) {
            case FraudHandler::FRAUD_MSG_LIST:
                return implode('; ', $value);
        }
        return parent::getValueView($field, $value);
    }

    /**
     * Get order object instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        if (!$this->_order) {
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
        if (is_null($order)) {
            return "";
        }

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();

        return $payment->getAdditionalInformation("paymentUrl");
    }

    public function getCancellationId()
    {
        $order = $this->getOrder();
        if (is_null($order)) {
            return "";
        }

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();

        return $payment->getAdditionalInformation("cancellationId");
    }

    public function getAuthorizationId()
    {
        $order = $this->getOrder();
        if (is_null($order)) {
            return "";
        }

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();

        return $payment->getAdditionalInformation("authorizationId");
    }

    public function getQrcode()
    {
        if ($paymentUrl = $this->getPaymentUrl()) {
            /** @var \Picpay\Payment\Helper\Data $picpayHelper */
            $picpayHelper = $this->paymentHelper;

            $imageSize = $picpayHelper->getQrcodeInfoWidth()
                ? $picpayHelper->getQrcodeInfoWidth()
                : $picpayHelper::DEFAULT_QRCODE_WIDTH;

            return $picpayHelper->generateQrCode($paymentUrl, $imageSize);
        }
    }
}