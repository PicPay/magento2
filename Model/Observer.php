<?php 

namespace Picpay\Payment\Model;

class Observer extends \Magento\Framework\Event\Observer
{

    /**
     * @var \Picpay\Payment\Helper\Autoloader
     */
    protected $paymentAutoloaderHelper;

    /**
     * @var \Magento\Framework\Session\Generic
     */
    protected $generic;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Picpay\Payment\Helper\Data
     */
    protected $paymentHelper;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $salesOrderFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $layout;

    public function __construct(
        \Picpay\Payment\Helper\Autoloader $paymentAutoloaderHelper,
        \Magento\Framework\Session\Generic $generic,
        \Psr\Log\LoggerInterface $logger,
        \Picpay\Payment\Helper\Data $paymentHelper,
        \Magento\Sales\Model\OrderFactory $salesOrderFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\View\LayoutInterface $layout,
        array $data = []
    ) {
        $this->paymentAutoloaderHelper = $paymentAutoloaderHelper;
        $this->generic = $generic;
        $this->logger = $logger;
        $this->paymentHelper = $paymentHelper;
        $this->salesOrderFactory = $salesOrderFactory;
        $this->registry = $registry;
        $this->checkoutSession = $checkoutSession;
        $this->layout = $layout;
        parent::__construct(
            $data
        );
    }

    /**
     * This is an observer function for the event 'controller_front_init_before'.
     * It prepends our autoloader, so we can load the extra libraries.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function controllerFrontInitBefore(/** @noinspection PhpUnusedParameterInspection */ $observer)
    {
        /** @var JeroenVermeulen_Solarium_Helper_Autoloader $autoLoader */
        $autoLoader = $this->paymentAutoloaderHelper;
        $autoLoader->register();
    }

    /**
     * Cancel payment transaction in PicPay api
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Picpay\Payment\Helper\Data $helper
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return $this
     */
    protected function _cancelOrder($order, $helper)
    {
        $payment = $order->getPayment();
        $return = $payment->getMethodInstance()->cancelRequest($order);

        $helper->log("Cancel Order Return");
        $helper->log($return);

        if(!is_array($return)) {
            $this->generic->addError($helper->__('Error while try refund order.'));
            return $this;
        }
        if($return['success'] == 0) {
            $this->generic->addError($helper->__('Error while try refund order.') . " " . $return['return']);
            return $this;
        }

        try {
            if(isset($return["return"]["cancellationId"])) {
                $payment->setAdditionalInformation("cancellationId", $return["return"]["cancellationId"]);
                $payment->save();
            }
            $this->generic->addSuccess($helper->__('Order canceled with success at Picpay.'));
        }
        catch (Exception $e) {
            $this->generic->addError($helper->__('Error while try refund order. '. $e->getMessage()));
            $this->logger->critical($e);
        }
        return $this;
    }

    /**
     * Cancel transacion via API PicPay by cancel order save event
     *
     * @param $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Picpay\Payment\Model\Observer
     */
    public function cancelTransaction($observer)
    {
        /** @var \Picpay\Payment\Helper\Data $helper */
        $helper = $this->paymentHelper;

        if(!$helper->isModuleEnabled()) {
            return $this;
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        if(!$order
            || !$order->getId()
            || $order->getPayment()->getMethodInstance()->getCode() != "picpay_standard"
        ) {
            return $this;
        }

        return $this->_cancelOrder($order, $helper);
    }

    /**
     * Refund transacion via API PicPay by creditmemo save event
     *
     * @param $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Picpay\Payment\Model\Observer
     */
    public function refundTransaction($observer)
    {
        /** @var \Picpay\Payment\Helper\Data $helper */
        $helper = $this->paymentHelper;

        if(!$helper->isModuleEnabled()) {
            return $this;
        }

        $creditmemo = $observer->getEvent()->getCreditmemo();

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->salesOrderFactory->create()->load($creditmemo->getOrderId());

        if(!$order
            || !$order->getId()
            || $order->getPayment()->getMethodInstance()->getCode() != "picpay_standard"
        ) {
            return $this;
        }

        return $this->_cancelOrder($order, $helper);
    }

    /**
     * Add button to actions on Order View
     *
     * @param $observer
     * @return \Picpay\Payment\Model\Observer
     */
    public function addOrderButtonsAction($observer)
    {
        /** @var \Picpay\Payment\Helper\Data $helper */
        $helper = $this->paymentHelper;

        if(!$helper->isModuleEnabled()) {
            return $this;
        }

        $block = $observer->getEvent()->getBlock();
        if ($block instanceof \Magento\Sales\Block\Adminhtml\Order\View) {
            $message = __('Are you sure you want to Sync Picpay Transaction?');

            $order = $this->registry->registry("sales_order");

            if($order && $order->getId()) {
                $block->addButton('picpay_sync',
                    array(
                        'label' => __('Sync Picpay Transaction'),
                        'onclick' => "confirmSetLocation('{$message}', '{$block->getUrl('adminhtml_picpay/adminhtml_index/consult')}')",
                        'class' => 'go'
                    )
                );
            }
        }
    }

    /**
     * Add qrcode block when mode is appropriate
     *
     * @param $observer
     * @return \Picpay\Payment\Model\Observer
     */
    public function addPicpayQrcodeBlock($observer)
    {
        /** @var \Picpay\Payment\Helper\Data $helper */
        $helper = $this->paymentHelper;

        if(!$helper->isModuleEnabled()
            || !$helper->isActive()
            || $helper->isRedirectMode()
        ) {
            return $this;
        }

        /** @var $_block Mage_Core_Block_Abstract */
        $_block = $observer->getBlock();
        $session = $this->checkoutSession;
        /** @var \Magento\Framework\View\LayoutInterface $layout */
        $layout = $this->layout;
        $handles = $layout->getUpdate()->getHandles();

        if ($_block->getType() == 'core/text_list'
            && $_block->getNameInLayout() == "content"
            && $session->getLastOrderId()
            && (
                in_array("checkout_onepage_success", $handles) == true ||
                in_array("checkout_multishipping_success", $handles) == true
            )
        ) {
            $template = $helper::PHTML_SUCCESS_PATH_IFRAME;
            if($helper->isOnpageMode()) {
                $template = $helper::PHTML_SUCCESS_PATH_ONPAGE;
            }

            $picpayBlock = $layout->createBlock(
                'Mage_Core_Block_Template',
                'picpay.qrcode.success',
                array('template' => $template)
            );
            $_block->append($picpayBlock);
        }
    }
}