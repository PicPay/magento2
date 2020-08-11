<?php

namespace Picpay\Payment\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Picpay\Payment\Helper\Data as Picpay;
use Psr\Log\LoggerInterface;
use Magento\Checkout\Model\Session;

class AuthorizationRequest implements BuilderInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var Picpay
     */
    private $picpay;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Session
     */
    private $session;

    /**
     * AuthorizationRequest constructor.
     * @param ConfigInterface $config
     * @param Picpay $picpay
     * @param LoggerInterface $logger
     * @param Session $session
     */
    public function __construct(
        ConfigInterface $config,
        Picpay $picpay,
        LoggerInterface $logger,
        Session $session
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->picpay = $picpay;
        $this->session = $session;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $payment */
        $payment = $buildSubject['payment'];
        $order   = $payment->getOrder();
        $address = $order->getShippingAddress();

        $version = $this->picpay->getVersion();
        $expiresAt = $this->picpay->getExpiresAt($order);
        $incrementId = $order->getOrderIncrementId();

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->session->getQuote();

        /**
         * @todo pegar
         * order id
         */
        $orderId = $order->getId();

        return [
            'TXN_TYPE'      => 'A',
            'referenceId'   => $incrementId,
            'callbackUrl'   => $this->picpay->getCallbackUrl(),
            'returnUrl'     => $this->picpay->getReturnUrl($orderId),
            'value'         => round($order->getGrandTotalAmount(), 2),
            'buyer'         => $this->picpay->getBuyer($order, $quote),
            'plugin'        => "Magento 2". $version,
            'api_url'       => $this->picpay->getApiUrl("/payments"),
            'expiresAt'     => $expiresAt
        ];
    }
}
