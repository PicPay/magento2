<?php

namespace Picpay\Payment\Gateway\Http\Client;

use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use Picpay\Payment\Helper\Data as Picpay;

class ClientMock implements ClientInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Picpay
     */
    private $picpay;

    /**
     * @param ZendClientFactory $clientFactory
     * @param Logger $logger
     * @param ConverterInterface | null $converter
     */
    public function __construct(
        Logger $logger,
        Picpay $picpay
    ) {
        $this->logger = $logger;
        $this->picpay = $picpay;
    }

    /**
     * Places request to gateway. Returns result as ENV array
     *
     * @param TransferInterface $transferObject
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $log = [
            'request'       => $transferObject->getBody(),
            'request_uri'   => $transferObject->getUri(),
            'token'         => $this->picpay->getToken(),
            'uri'         => $transferObject->getUri(),
            'body'         => $transferObject->getBody(),
        ];

        $result = [];

        try {
            $result = $this->picpay->requestApi(
                $transferObject->getUri(),
                $transferObject->getBody()
            );
//            $result = ['success' => 1];
            $log['response'] = $result;
        } catch (Exception $e) {
            throw $e;
        } finally {
            $this->logger->debug($log);
        }

        return $result;
    }
}
