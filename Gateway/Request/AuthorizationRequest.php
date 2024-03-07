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
        /** @var \Magento\Sales\Model\Order\Payment\Interceptor $payment */
        $payment = $buildSubject['payment']->getPayment();
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        $version = $this->picpay->getVersion();
        $expiresAt = $this->picpay->getExpiresAt();
        $incrementId = $order->getRealOrderId();

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->session->getQuote();

        return [
            'TXN_TYPE'      => 'A',
            'referenceId'   => $incrementId,
            'callbackUrl'   => $this->picpay->getCallbackUrl(),
            'returnUrl'     => $this->picpay->getReturnUrl(),
            'value'         => round((float) $buildSubject['amount'], 2),
            'buyer'         => $this->picpay->getBuyer($order, $quote),
            'plugin'        => "Magento 2". $version,
            'api_url'       => $this->picpay->getApiUrl("/payments"),
            'expiresAt'     => $expiresAt
        ];
    }
}
