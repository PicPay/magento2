<?php

namespace Picpay\Payment\Gateway\Response;

use InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Psr\Log\LoggerInterface;

class TxnIdHandler implements HandlerInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     *
     * @throws LocalizedException
     * @throws InvalidArgumentException
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $this->validatePicPayResponse($response);

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $handlingSubject['payment'];
        $payment = $paymentDO->getPayment();

        $payment->setIsTransactionPending(true);
        $payment->getOrder()->setCanSendNewEmailFlag(true);

        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment->setTransactionId($response['return']['referenceId']);
        $payment->setIsTransactionClosed(false);
        $payment->setAdditionalInformation('paymentUrl', $response['return']['paymentUrl']);
        $payment->setAdditionalInformation('qrcode', $response['return']['qrcode']);
    }

    /**
     * @param array $response
     * @return void
     * @throws InvalidArgumentException
     */
    private function validatePicPayResponse(array $response): void
    {
        try {
            $this->validateRequiredFields($response);
        } catch (InvalidArgumentException $exception) {
            $context = [
                'message' => $exception->getMessage(),
            ];

            if (isset($response['return']['errors'])) {
                $context['errors'] = array_column($response['return']['errors'], 'message');
            }

            $this->logger->error('picpay payment error', $context);

            throw $exception;
        }
    }

    private function validateRequiredFields(array $response): void
    {
        $errorMessage = $response['return']['message'] ?? null;

        if ($response['success'] === 0) {
            throw new InvalidArgumentException("Unexpected payment error. Details [$errorMessage]");
        }

        if (!$this->isValidStringField($response, 'referenceId')) {
            throw new InvalidArgumentException($errorMessage ?? 'Resposta sem o campo referenceId');
        }

        if (!$this->isValidStringField($response, 'paymentUrl')) {
            throw new InvalidArgumentException($errorMessage ?? 'Resposta sem o campo paymentUrl');
        }

        if (!isset($response['return']['qrcode']['content'])) {
            throw new InvalidArgumentException($errorMessage ?? 'Resposta sem o campo qrcode.content');
        }

        if (!isset($response['return']['qrcode']['base64'])) {
            throw new InvalidArgumentException($errorMessage ?? 'Resposta sem o campo qrcode.base64');
        }
    }

    private function isValidStringField(array $response, string $field): bool
    {
        return isset($response['return'][$field])
            && is_string($response['return'][$field])
            && strlen(trim($response['return'][$field])) > 0;
    }
}