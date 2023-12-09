<?php

namespace Picpay\Payment\Block;

use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;
use Picpay\Payment\Helper\Data;

class Success extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var Order
     */
    protected $order;

    public function __construct(
        Template\Context $context,
        Data $helper,
        Session $checkoutSession,
        array $data = []
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->helper = $helper;
        $this->order = $this->_checkoutSession->getLastRealOrder();
        parent::__construct($context, $data);
    }

    public function getHelper(): Data
    {
        return $this->helper;
    }

    public function getOrder()
    {
        return $this->order;
    }
}
