<?php

namespace Picpay\Payment\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Picpay\Payment\Gateway\Http\Client\ClientMock;

/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'picpay_standard';

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'isActive' => true,
                ]
            ]
        ];
    }
}