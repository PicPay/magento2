<?php

namespace Picpay\Payment\Model\Source;

class Mode
{
    /**
     * @var \Picpay\Payment\Helper\Data
     */
    protected $paymentHelper;

    public function __construct(
        \Picpay\Payment\Helper\Data $paymentHelper
    ) {
        $this->paymentHelper = $paymentHelper;
    }
    
    public function toOptionArray()
    {
        /** @var \Picpay\Payment\Helper\Data $picpayHelper */
        $picpayHelper = $this->paymentHelper;

        return array(
            array('value' => $picpayHelper::ONPAGE_MODE, 'label' => 'On Page'),
            array('value' => $picpayHelper::IFRAME_MODE, 'label' => 'Iframe'),
            array('value' => $picpayHelper::REDIRECT_MODE, 'label' => 'Redirect')
        );
    }
}
