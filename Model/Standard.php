<?php

namespace Picpay\Payment\Model;

class Standard extends \Magento\Payment\Model\Method\AbstractMethod
{
    protected $_code = 'picpay_standard';
    protected $_formBlockType = 'picpay_payment/form_picpay';
    protected $_infoBlockType = 'picpay_payment/info';

    protected $_canOrder = true;
    protected $_isInitializeNeeded = false;

    protected $_canUseInternal = true;
    protected $_canUseForMultishipping = true;
    protected $_canUseCheckout = true;
    protected $_canRefundInvoicePartial = false;

    /** @var \Picpay\Payment\Helper\Data $_helperPicpay */
    protected $_helperPicpay = null;

    /**
     * @var \Picpay\Payment\Helper\Data
     */
    protected $paymentHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Session\Generic
     */
    protected $generic;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    protected $dataObjectFactory;

    public function __construct(
        \Picpay\Payment\Helper\Data $paymentHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Session\Generic $generic,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\DataObjectFactory $dataObjectFactory
    ) {
        $this->dataObjectFactory = $dataObjectFactory;
        $this->paymentHelper = $paymentHelper;
        $this->storeManager = $storeManager;
        $this->generic = $generic;
        $this->logger = $logger;
    }
    public function getConfigPaymentAction()
    {
        return \Magento\Payment\Model\Method\AbstractMethod::ACTION_ORDER;
    }

    /**
     * Get PicPay Helper
     *
     * @return \Picpay\Payment\Helper\Data
     */
    public function _getHelper()
    {
        if(is_null($this->_helperPicpay)) {
            $this->_helperPicpay = $this->paymentHelper;
        }
        return $this->_helperPicpay;
    }

    /**
     * Check if order total is zero making method unavailable
     * @param \Magento\Quote\Model\Quote $quote
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return mixed
     */
    public function isAvailable($quote = null)
    {
        return parent::isAvailable($quote) && !empty($quote)
            && $this->storeManager->getStore()->roundPrice($quote->getGrandTotal()) > 0;
    }

    /**
     * Assign data to info model instance
     *
     * @param   mixed $data
     * @return \Picpay\Payment\Model\Standard
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function assignData($data)
    {
        if (!($data instanceof \Magento\Framework\DataObject)) {
            $data = $this->dataObjectFactory->create($data);
        }
        if ($data instanceof \Magento\Framework\DataObject) {
            $this->getInfoInstance()->addData($data->getData());
        }

        $info = $this->getInfoInstance();
        
        $info->setAdditionalInformation('return_url', $this->_getHelper()->getReturnUrl());
        $info->setAdditionalInformation('mode_checkout', $this->_getHelper()->getCheckoutMode());
        
        return $this;
    }

    /**
     * Return Order place redirect url
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getOrderPlaceRedirectUrl()
    {
        if($this->_getHelper()->isRedirectMode()) {
            $paymentUrl = $this->generic->getPicpayPaymentUrl();

            if ($paymentUrl) {
                return $paymentUrl;
            }
            else {
                throw new \Magento\Framework\Exception\LocalizedException($this->_getHelper()->__("Invalid payment url"));
            }
        }

        $isSecure = $this->storeManager->getStore()->isCurrentlySecure();
        return Mage::getUrl('checkout/onepage/success', array('_secure' => $isSecure));
    }

    /**
     * Consult transaction via API
     * 
     * @param \Magento\Sales\Model\Order $order
     * @return bool|mixed|string
     */
    public function consultRequest($order)
    {
        $result = $this->_getHelper()->requestApi(
            $this->_getHelper()->getApiUrl("/payments/{$order->getIncrementId()}/status"),
            array(),
            "GET"
        );

        if(isset($result['success'])) {
            return $result;
        }

        return false;
    }


    /**
     * Request cancel transaction via API
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool|mixed
     */
    public function paymentRequest($order)
    {
        $data = array(
            'referenceId'   => $order->getIncrementId(),
            'callbackUrl'   => $this->_getHelper()->getCallbackUrl(),
            'returnUrl'     => $this->_getHelper()->getReturnUrl(),
            'value'         => round($order->getGrandTotal(), 2),
            'buyer'         => $this->_getHelper()->getBuyer($order)
        );

        $result = $this->_getHelper()->requestApi(
            $this->_getHelper()->getApiUrl("/payments"),
            $data
        );

        if(isset($result['success'])) {
            return $result;
        }

        return false;
    }

    /**
     * Request cancel transaction via API
     * 
     * @param \Magento\Sales\Model\Order $order
     * @return bool|mixed
     */
    public function cancelRequest($order)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();

        $data = array();
        $authorizationId = $payment->getAdditionalInformation("authorizationId");

        if($authorizationId) {
            $data["authorizationId"] = $authorizationId;
        }

        $result = $this->_getHelper()->requestApi(
            $this->_getHelper()->getApiUrl("/payments/{$order->getIncrementId()}/cancellations"),
            $data
        );

        if(isset($result['success'])) {
            return $result;
        }

        return false;
    }

    /**
     * Authorize payment picpay_standard method
     *
     * @param \Magento\Framework\DataObject $payment
     * @param float $amount
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return \Picpay\Payment\Model\Standard
     */
    public function order(\Magento\Framework\DataObject $payment, $amount)
    {
        $payment->setSkipOrderProcessing(true);

        parent::order($payment, $amount);

        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        $this->_getHelper()->log("Order Model Payment Method");

        $return = $this->paymentRequest($order);

        if(!is_array($return)) {
            throw new \Magento\Framework\Exception\LocalizedException($this->_getHelper()->__('Unable to process payment. Contact Us.'));
        }
        if($return['success'] == 0) {
            throw new \Magento\Framework\Exception\LocalizedException($this->_getHelper()->__($return['return']));
        }

        try {
            $payment->setAdditionalInformation("paymentUrl", $return["return"]["paymentUrl"]);
            $payment->save();
            $this->generic->setPicpayPaymentUrl($return["return"]["paymentUrl"]);
        }
        catch (Exception $e) {
            $this->logger->critical($e);
        }

        return $this;
    }

    /**
     * Void payment picpay_standard method
     *
     * @param \Magento\Framework\DataObject $payment
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return \Picpay\Payment\Model\Standard
     */
    public function void(\Magento\Framework\DataObject $payment)
    {
        parent::void($payment);

        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        $this->_getHelper()->log("Void Model Payment Method");

        $return = $this->cancelRequest($order);

        if(!is_array($return)) {
            throw new \Magento\Framework\Exception\LocalizedException($this->_getHelper()->__('Unable to process void payment. Contact Us.'));
        }
        if($return['success'] == 0) {
            throw new \Magento\Framework\Exception\LocalizedException($this->_getHelper()->__($return['return']));
        }

        try {
            $payment->setAdditionalInformation("cancellationId", $return["return"]["cancellationId"]);
            $payment->save();
        }
        catch (Exception $e) {
            $this->logger->critical($e);
        }

        return $this;
    }

    /**
     * Refund specified amount for picpay_standard payment
     *
     * @param \Magento\Framework\DataObject $payment
     * @param float $amount
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return \Picpay\Payment\Model\Standard
     */
    public function refund(\Magento\Framework\DataObject $payment, $amount)
    {
        parent::refund($payment, $amount);

        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        $this->_getHelper()->log("Refund Model Payment Method");

        $return = $this->cancelRequest($order);

        if(!is_array($return)) {
            throw new \Magento\Framework\Exception\LocalizedException($this->_getHelper()->__('Unable to process refund payment. Contact Us.'));
        }
        if($return['success'] == 0) {
            throw new \Magento\Framework\Exception\LocalizedException($this->_getHelper()->__($return['return']));
        }

        try {
            $payment->setAdditionalInformation("cancellationId", $return["return"]["cancellationId"]);
            $payment->save();
        }
        catch (Exception $e) {
            $this->logger->critical($e);
        }

        return $this;
    }
}