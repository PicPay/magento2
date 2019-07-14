<?php


namespace Picpay\Payment\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use Picpay\Payment\Helper\Data;

class InitializeCommand implements CommandInterface
{
    /**
     * @var Data
     */
    protected $_helper;

    /**
     * InitializeCommand constructor.
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    )
    {
        $this->_helper =  $helper;
    }

    /**
     * @param array $commandSubject
     * @return $this|\Magento\Payment\Gateway\Command\ResultInterface|null
     */
    public function execute(array $commandSubject)
    {
        $payment =\Magento\Payment\Gateway\Helper\SubjectReader::readPayment($commandSubject);
        $stateObject = \Magento\Payment\Gateway\Helper\SubjectReader::readStateObject($commandSubject);

        // do not send email
        $payment = $payment->getPayment();
        $payment->getOrder()->setCanSendNewEmailFlag(false);

        $baseTotalDue = $payment->getOrder()->getBaseTotalDue();
        $totalDue = $payment->getOrder()->getTotalDue();
        $payment->authorize(true, $baseTotalDue);
        $payment->setAmountAuthorized($totalDue);
        $payment->setBaseAmountAuthorized($payment->getOrder()->getBaseTotalDue());

        // update status and state
        $stateObject->setState(\Magento\Sales\Model\Order::STATE_NEW);
        $stateObject->setStatus($this->_helper->getStoreConfig('order_status'));
        $stateObject->setIsNotified(false);

        return $this;
    }
}