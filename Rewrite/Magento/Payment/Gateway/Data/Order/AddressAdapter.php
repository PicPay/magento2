<?php

namespace Picpay\Payment\Rewrite\Magento\Payment\Gateway\Data\Order;

use Magento\Sales\Api\Data\OrderAddressInterface;

class AddressAdapter extends \Magento\Payment\Gateway\Data\Order\AddressAdapter
{
    /**
     * @var OrderAddressInterface
     */
    private $address;

    /**
     * @param OrderAddressInterface $address
     */
    public function __construct(OrderAddressInterface $address)
    {
        parent::__construct($address);
        $this->address = $address;
    }

    /**
     * Get company
     *
     * @return string
     */
    public function getVat()
    {
        return $this->address->getCompany();
    }
}

