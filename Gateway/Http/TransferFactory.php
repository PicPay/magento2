<?php

namespace Picpay\Payment\Gateway\Http;

use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Picpay\Payment\Helper\Data as Picpay;

class TransferFactory implements TransferFactoryInterface
{
    /**
     * @var TransferBuilder
     */
    private $transferBuilder;

    /**
     * @var Picpay
     */
    private $picpay;

    /**
     * @param TransferBuilder $transferBuilder
     * @param Picpay $picpay
     */
    public function __construct(
        TransferBuilder $transferBuilder,
        Picpay $picpay
    )
    {
        $this->transferBuilder = $transferBuilder;
        $this->picpay = $picpay;
    }

    /**
     * Builds gateway transfer object
     *
     * @param array $request
     * @return TransferInterface
     */
    public function create(array $request)
    {
        $apiUrl = $request['api_url'];
        unset($request['api_url']);

        return $this->transferBuilder
            ->setMethod('POST')
            ->setHeaders(
                [
                    "x-picpay-token: {$this->picpay->getToken()}",
                    "cache-control: no-cache",
                    "content-type: application/json"
                ]
            )
            ->setBody(json_encode($request, JSON_UNESCAPED_SLASHES))
            ->setUri($apiUrl)
            ->build();
    }
}
