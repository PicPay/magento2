<?php

namespace Picpay\Payment\Model\Source\Customer;

class Taxvat
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
    
    /**
     * @return array
     */
    public function toOptionArray()
    {
        /** @var \Picpay\Payment\Helper\Data $picpayHelper */
        $picpayHelper = $this->paymentHelper;
        $fields = $picpayHelper->getFields('customer');

        $options = array();
        $options[] = array(
            'value' => '',
            'label' => $picpayHelper->__('Select the taxvat attribute')
        );
        foreach ($fields as $key => $value) {
            if (!is_null($value['frontend_label'])) {
                $options['customer|'.$value['frontend_label']] = array(
                    'value' => 'customer|'.$value['attribute_code'],
                    'label' => 'Customer: '.$value['frontend_label'] . ' (' . $value['attribute_code'] . ')'
                );
            }
        }

        $addressFields = $picpayHelper->getFields('customer_address');
        foreach ($addressFields as $key => $value) {
            if (!is_null($value['frontend_label'])) {
                $options['address|'.$value['frontend_label']] = array(
                    'value' => 'billing|'.$value['attribute_code'],
                    'label' => 'Billing: '.$value['frontend_label'] . ' (' . $value['attribute_code'] . ')'
                );
            }
        }

        return $options;
    }
}