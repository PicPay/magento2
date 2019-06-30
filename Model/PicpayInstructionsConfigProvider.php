<?php

namespace Picpay\Payment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Picpay\Payment\Helper\Data as PaymentHelper;

class PicpayInstructionsConfigProvider implements ConfigProviderInterface
{

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;
    /**
     * @param PaymentHelper $paymentHelper
     * @param Escaper $escaper
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        Escaper $escaper
    ) {
        $this->escaper = $escaper;
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config = [];
        $config['payment']['picpay_instructions'] = $this->getInstructions();
        $config['payment']['picpay_checkout_mode'] = $this->paymentHelper->getCheckoutMode();
        return $config;
    }

    /**
     * Get instructions text from config
     *
     * @param string $code
     * @return string
     */
    protected function getInstructions()
    {
        $instructions = "";
        if($this->paymentHelper->useCustomForm()) {
            $instructions = $this->paymentHelper->getCustomHtmlForm();
        }
        else {
            $instructions = '<img width="150px" src="https://ecommerce.picpay.com/doc/assets/picpay-logo.svg" alt="PicPay" '
                . 'style="background-color: rgb(33, 194, 94); border: 0; padding: 10px;" />'
                . '<br/>'
                . '<p>Não conhece o PicPay? '
                . '<a href="https://www.picpay.com/site" target="_blank">Clique aqui</a>'
                . ' e baixe agora para efetuar seu pagamento.</p>';
        }
        return $instructions;
    }
}