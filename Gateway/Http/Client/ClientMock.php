<?php

namespace Picpay\Payment\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use Picpay\Payment\Helper\Data;

class ClientMock implements ClientInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @param Logger $logger
     * @param Data $helper
     */
    public function __construct(
        Logger $logger,
        Data $helper
    ) {
        $this->logger = $logger;
        $this->helper = $helper;
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
            'token'         => $this->helper->getToken(),
            'uri'         => $transferObject->getUri(),
            'body'         => $transferObject->getBody(),
        ];

        $result = [];

        try {
            $result = $this->helper->requestApi(
                $transferObject->getUri(),
                $transferObject->getBody()
            );
            $log['response'] = $result;
        } catch (\Exception $e) {
            throw $e;
        } finally {
            $this->logger->debug($log);
        }

        return $result;
    }
}
