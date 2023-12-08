<?php

namespace Picpay\Payment\Block\Form;

use Magento\Framework\View\Element\Template;
use Picpay\Payment\Helper\Data;

class Picpay extends \Magento\Payment\Block\Form
{

    protected $helper;

    public function __construct(
        Data $helper,
        Template\Context $context,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context,$data);
    }
    /**
     * Especifica template.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('picpay/form/picpay.phtml');
    }

    public function getHelper(): Data
    {
        return $this->helper;
    }
}
