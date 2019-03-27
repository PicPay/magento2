<?php

namespace Picpay\Payment\Model\Source\Customer;

class Address
{
    /**
     * @var \Picpay\Payment\Helper\Data
     */
    protected $paymentHelper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(
        \Picpay\Payment\Helper\Data $paymentHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->scopeConfig = $scopeConfig;
    }
    /**
     * Return Address attribute
     * @return array
     */
    public function toOptionArray()
    {
        /** @var \Picpay\Payment\Helper\Data $picpayHelper */
        $picpayHelper = $this->paymentHelper;
        $fields = $picpayHelper->getFields('customer_address');
        $options = array();

        foreach ($fields as $key => $value) {
            if (!is_null($value['frontend_label'])) {
                if ($value['attribute_code'] == 'street') {
                    $streetLines = $this->scopeConfig->getValue('customer/address/street_lines', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
                    for ($i = 1; $i <= $streetLines; $i++) {
                        $options[] = array('value' => 'street_'.$i, 'label' => 'Street Line '.$i);
                    }
                } else {
                    $options[] = array(
                        'value' => $value['attribute_code'],
                        'label' => $value['frontend_label'] . ' (' . $value['attribute_code'] . ')'
                    );
                }
            }
        }
        return $options;
    }
}
