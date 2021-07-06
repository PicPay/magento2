<?php

namespace Picpay\Payment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Picpay\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\View\Element\Template;

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

    protected $template;

    public function __construct(
        PaymentHelper $paymentHelper,
        Escaper $escaper,
        Template $template
    ) {
        $this->escaper = $escaper;
        $this->paymentHelper = $paymentHelper;
        $this->template = $template;
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

            $instructions = $this->template->getLayout()->createBlock('Magento\Framework\View\Element\Template')->setTemplate('Picpay_Payment::CustomForm.phtml')->toHtml();

        }
        return $instructions;
    }
}