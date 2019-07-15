<?php

namespace Picpay\Payment\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Picpay\Payment\Helper\Data as Picpay;

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
     * @param ConfigInterface $config
     * @param Picpay $picpay
     */
    public function __construct(
        ConfigInterface $config,
        Picpay $picpay
    )
    {
        $this->config = $config;
        $this->picpay = $picpay;
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
            'buyer'         => $this->picpay->getBuyer($order),
            'plugin'        => "Magento 2". $version,
            'api_url'       => $this->picpay->getApiUrl("/payments"),
            'expiresAt'     => $expiresAt
        ];
    }
}
