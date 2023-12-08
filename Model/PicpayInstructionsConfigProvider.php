<?php

namespace Picpay\Payment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Framework\View\Asset\Repository as AssetsRepository;
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
     * @var AssetsRepository
     */
    protected $assetsRepository;

    /**
     * @param PaymentHelper $paymentHelper
     * @param Escaper $escaper
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        Escaper $escaper,
        AssetsRepository $assetsRepository
    ) {
        $this->escaper = $escaper;
        $this->paymentHelper = $paymentHelper;
        $this->assetsRepository = $assetsRepository;
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
     * @return string
     */
    protected function getInstructions(): string
    {
        if($this->paymentHelper->useCustomForm()) {
            return $this->paymentHelper->getCustomHtmlForm();
        }

        $logoAddress = $this->assetsRepository->getUrl('Picpay_Payment::images/picpay-logo.svg');

        return '<img width="150px" src="' . $logoAddress . '" alt="PicPay Logo" '
            . 'style="background-color: rgb(33, 194, 94); border: 0; padding: 10px;" />'
            . '<br/>'
            . '<p>Não conhece o PicPay? '
            . '<a href="https://www.picpay.com/site" target="_blank">Clique aqui</a>'
            . ' e baixe agora para efetuar seu pagamento.</p>';
    }
}
