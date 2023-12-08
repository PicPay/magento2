<?php

namespace Picpay\Payment\Model\Source;

class Mode implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Picpay\Payment\Helper\Data
     */
    protected $_paymentHelper;

    /**
     * Mode constructor.
     *
     * @param \Picpay\Payment\Helper\Data $paymentHelper
     */
    public function __construct(
        \Picpay\Payment\Helper\Data $paymentHelper
    ) {
        $this->_paymentHelper = $paymentHelper;
    }

    public function toOptionArray()
    {
        /** @var \Picpay\Payment\Helper\Data $picpayHelper */
        $picpayHelper = $this->_paymentHelper;

        return [
            ['value' => $picpayHelper::ONPAGE_MODE, 'label' => 'On Page'],
            ['value' => $picpayHelper::REDIRECT_MODE, 'label' => 'Redirect']
        ];
    }
}
