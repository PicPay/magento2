<?php

namespace Picpay\Payment\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;

class RefundHandler implements HandlerInterface
{
    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }
        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $handlingSubject['payment'];
        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment = $paymentDO->getPayment();

        if($response["success"] == 1) {
            if(isset($response["return"]["cancellationId"])) {
                $payment->setAdditionalInformation("cancellationId", $response["return"]["cancellationId"]);
            }
        }
        else {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Error on refund or void transaction')
            );
        }
    }
}