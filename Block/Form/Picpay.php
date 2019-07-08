<?php
namespace Picpay\Payment\Block\Form;


class Picpay extends \Magento\Payment\Block\Form
{
    /**
     * Especifica template.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('picpay/form/picpay.phtml');
    }
}