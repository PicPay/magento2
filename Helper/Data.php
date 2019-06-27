<?php

namespace Picpay\Payment\Helper;

use Magento\Config\Model\Config\Backend\Admin\Custom;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\UrlInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\HTTP\Adapter\Curl;

class Data extends AbstractHelper
{
    const API_URL       = "https://appws.picpay.com/ecommerce/public";
    const MODULE_NAME   = "Picpay_Payment";
    const ONPAGE_MODE   = 1;
    const IFRAME_MODE   = 2;
    const REDIRECT_MODE = 3;

    const XML_PATH_SYSTEM_CONFIG    = "payment/picpay_standard";
    const SUCCESS_PATH_URL          = "checkout/onepage/success";
    const SUCCESS_IFRAME_PATH_URL   = "picpay/standard/success";

    const PHTML_SUCCESS_PATH_ONPAGE = "picpay/success.qrcode.phtml";
    const PHTML_SUCCESS_PATH_IFRAME = "picpay/success.iframe.phtml";

    const DEFAULT_QRCODE_WIDTH      = 150;
    const DEFAULT_IFRAME_HEIGHT     = 300;

    /**
     * Store
     * @var bool|\Magento\Store\Model\Store
     */
    protected $_store = false;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Eav\Model\ConfigFactory
     */
    protected $eavConfigFactory;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory
     */
    protected $eavResourceModelEntityAttributeCollectionFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $backendSession;

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @var \Magento\Sales\Model\Order\StatusFactory
     */
    protected $salesOrderStatusFactory;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var ModuleListInterface
     */
    protected $moduleList;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepositoryInterface;

    /**
     * @var \Magento\Framework\HTTP\Adapter\Curl $curl
     */
    protected $curl;

    /**
     * Construtor
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Eav\Model\ConfigFactory $eavConfigFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory $eavResourceModelEntityAttributeCollectionFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Backend\Model\Session $backendSession,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Sales\Model\Order\StatusFactory $salesOrderStatusFactory,
        UrlInterface $urlBuilder,
        ModuleListInterface $moduleList,
        CustomerRepositoryInterface $customerRepositoryInterface,
        Curl $curl
    )
    {
        parent::__construct($context);

        $this->storeManager = $storeManager;
        $this->eavConfigFactory = $eavConfigFactory;
        $this->eavResourceModelEntityAttributeCollectionFactory = $eavResourceModelEntityAttributeCollectionFactory;
        $this->logger = $logger;
        $this->backendSession = $backendSession;
        $this->transactionFactory = $transactionFactory;
        $this->salesOrderStatusFactory = $salesOrderStatusFactory;
        $this->urlBuilder = $urlBuilder;
        $this->moduleList = $moduleList;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->curl = $curl;

        if(is_null($this->_store)) {
            $this->_store = $this->storeManager->getStore();
        }
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return true;
    }

    /**
     * Get config from system configs module
     *
     * @param string $path config path
     * @return string value
     */
    public function getStoreConfig($path)
    {
        return $this->scopeConfig->getValue( self::XML_PATH_SYSTEM_CONFIG . '/' . $path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE );
    }

    /**
     * Get object store
     */
    public function getStore()
    {
        if($this->_store) {
            return $this->_store;
        }
        return $this->_store = $this->storeManager->getStore();
    }

    /**
     * Check if picpay payment is enabled
     *
     * @return string
     */
    public function isActive()
    {
        return $this->getStoreConfig("active");
    }

    /**
     * Get mode of checkout
     *
     * @return string
     */
    public function getCheckoutMode()
    {
        return $this->getStoreConfig("mode");
    }

    /**
     * Check if mode is On Page
     *
     * @return string
     */
    public function isOnpageMode()
    {
        return $this->getCheckoutMode() == self::ONPAGE_MODE;
    }

    /**
     * Check if mode is Iframe
     *
     * @return string
     */
    public function isIframeMode()
    {
        return $this->getCheckoutMode() == self::IFRAME_MODE;
    }

    /**
     * Check if mode is Redirect
     *
     * @return string
     */
    public function isRedirectMode()
    {
        return $this->getCheckoutMode() == self::REDIRECT_MODE;
    }

    /**
     * Get Picpay Token for API
     *
     * @return string
     */
    public function getToken()
    {
        return $this->getStoreConfig("token");
    }

    /**
     * Get Seller Token for API
     *
     * @return string
     */
    public function getSellerToken()
    {
        return $this->getStoreConfig("seller_token");
    }

    /**
     * Get qrcode width info
     *
     * @return string
     */
    public function getQrcodeInfoWidth()
    {
        $value = $this->getStoreConfig("qrcode_info_width");
        return $value ? $value : self::DEFAULT_QRCODE_WIDTH;
    }

    /**
     * Get qrcode width info
     *
     * @return string
     */
    public function getQrcodeOnpageWidth()
    {
        $value = $this->getStoreConfig("onpage_width");
        return $value ? $value : self::DEFAULT_QRCODE_WIDTH;
    }

    /**
     * Get module's version
     *
     * @return mixed
     */
    public function getVersion()
    {
        return " - v".$this->moduleList
            ->getOne(self::MODULE_NAME)['setup_version'];
    }

    /**
     * Get iframe style on iframe mode
     */
    public function getIframeStyle()
    {
        $valueW = $this->getStoreConfig("iframe_width");
        $valueH = $this->getStoreConfig("iframe_height");
        $width = $valueW ? $valueW : self::DEFAULT_QRCODE_WIDTH;
        $height = $valueH ? $valueH : self::DEFAULT_IFRAME_HEIGHT;

        $style = "";
        $style .= "margin: 20px auto;";
        $style .= "width: {$width}px;";
        $style .= "height: {$height}px;";
        return $style;
    }

    /**
     * Check if notification enabled
     *
     * @return string
     */
    public function isNotificationEnabled()
    {
        return $this->getStoreConfig("notification");
    }

    /**
     * Get API url to do request to API
     *
     * @param string $method
     * @return string
     */
    public function getApiUrl($method = "")
    {
        return self::API_URL . $method;
    }

    /**
     * Get flat to use or not custom form html
     *
     * @return string
     */
    public function useCustomForm()
    {
        return $this->getStoreConfig("use_custom_form");
    }

    /**
     * Get custom HTML Form
     *
     * @return string
     */
    public function getCustomHtmlForm()
    {
        return $this->getStoreConfig("custom_form_html");
    }

    /**
     * Get message to show on success page
     *
     * @return string
     */
    public function getMessageOnpageSuccess()
    {
        return $this->getStoreConfig("onpage_message");
    }

    /**
     * Get message to show on callback iframe
     *
     * @return string
     */
    public function getMessageIframeCallback()
    {
        return $this->getStoreConfig("iframe_message");
    }

    /**
     * Get fields from a given entity
     *
     * @author Gabriela D'Ávila (http://davila.blog.br)
     * @param $type
     * @return mixed
     */
    public function getFields($type = 'customer_address')
    {
        $entityType = $this->eavConfigFactory->create()->getEntityType($type);
        $entityTypeId = $entityType->getEntityTypeId();
        $attributes = $this->eavResourceModelEntityAttributeCollectionFactory->create()->setEntityTypeFilter($entityTypeId);
        return $attributes->getData();
    }

    /**
     * Check if current requested URL is secure
     *
     * @return boolean
     */
    public function isCurrentlySecure()
    {
        return $this->storeManager->getStore()->isCurrentlySecure();
    }

    /**
     * @return UrlInterface
     */
    public function getUrlBuilder()
    {
        return $this->urlBuilder;
    }

    /**
     * Get URL to return to store
     */
    public function getReturnUrl()
    {
        $isSecure = $this->storeManager->getStore()->isCurrentlySecure();

        if($this->isIframeMode()) {
            return $this->urlBuilder->getUrl(
                self::SUCCESS_IFRAME_PATH_URL,
                array("_secure" => $this->isCurrentlySecure())
            );
        }
        return $this->urlBuilder->getUrl(
            self::SUCCESS_PATH_URL,
            array("_secure" => $this->isCurrentlySecure())
        );
    }

    /**
     * Get URL to return to store
     */
    public function getCallbackUrl()
    {
        return $this->urlBuilder->getUrl(
            'picpay/notification/',
            array("_secure" => $this->isCurrentlySecure())
        );
    }

    /**
     * Validate a HTTP Request Authorization
     *
     * @param \Zend_Controller_Request_Http $request
     * @throws \Zend_Controller_Request_Exception
     * @return bool
     */
    public function validateAuth(\Zend_Controller_Request_Http $request)
    {
        // Validate system config values
        if (!$this->getSellerToken()) {
            return false;
        }

        // Validate Authorization string
        if (false == ($token = $request->getHeader('x-seller-token'))) {
            return false;
        }

        return ($token == $this->getSellerToken());
    }

    /**
     * Log function to debug
     *
     * @param mixed
     */
    public function log($data)
    {
        if($this->getStoreConfig("debug")) {
            $this->logger->debug($data);
        }
    }

    /**
     * cURL request to PicPay API
     *
     * @param $url
     * @param $fields
     * @param string $type
     * @param integer $timeout
     * @return array
     */
    public function requestApi($url, $fields, $type = "POST", $timeout = 10)
    {
        $tokenApi = $this->getToken();

        try {
            $headers = [
                "x-picpay-token: {$tokenApi}",
                "cache-control: no-cache",
                "content-type: application/json"
            ];

            $this->curl->setConfig([
                'verifypeer' => false,
                'verifyhost' => false,
                'timeout'    => $timeout
            ]);

            $this->log("JSON sent to PicPay API. URL: ".$url);
            $this->log((is_array($fields) ? \json_encode($fields) : $fields));

            $this->curl->write('POST',
                $url,
                $http_ver = '1.1',
                $headers,
                (is_array($fields) ? \json_encode($fields) : $fields)
            );

            $response = $this->curl->read();

            $this->log("JSON Response from PicPay API");
            $this->log($response);

            $httpCode = Zend_Http_Response::extractCode($response);

            if ($err || ($httpCode != 200 && $httpCode == 201)) {
                return array (
                    'success' => 0,
                    'return' => ($err ? $err : $response)
                );
            } else {
                return array (
                    'success' => 1,
                    'return' => \json_decode(trim($response), true)
                );
            }
//            return array (
//                'success' => 1,
//                'return' => []
//            );
        }
        catch (Exception $e) {
            $this->log("ERROR on requesting API: " . $e->getMessage());
            $this->logger->critical($e);

            return array (
                'success' => 0,
                'return' => $e->getMessage()
            );
        }
    }

    /**
     * Get buyer object from Order
     *
     * @param \Magento\Payment\Gateway\Data\Order\OrderAdapter $order
     * @return array
     */
    public function getBuyer($order) {
        /** @var \Magento\Payment\Gateway\Data\Order\AddressAdapter $billingAddress */
        $billingAddress = $order->getBillingAddress();

        $customerId = $order->getCustomerId();
        $customer = $this->customerRepositoryInterface->getById($customerId);

        $taxvat = false;
        if($customer && $customer->getId()) {
            $taxvat = $customer->getTaxvat();
        }

        $buyerFirstname = $billingAddress->getFirstname();
        $buyerLastname = $billingAddress->getLastname();
        $buyerDocument = $taxvat;
        $buyerEmail = $billingAddress->getEmail();
        $buyerPhone = $this->_extractPhone($billingAddress->getTelephone());

        return array(
            "firstName" => $buyerFirstname,
            "lastName"  => $buyerLastname,
            "document"  => $buyerDocument,
            "email"     => $buyerEmail,
            "phone"     => $buyerPhone
        );
    }

    /**
     * Get telephone attribute code
     *
     * @return string
     */
    protected function _getTelephoneAttribute()
    {
        return $this->getStoreConfig("address_telephone_attribute");
    }

    /**
     * Extracts phone area code and returns phone number
     *
     * @param string $phone
     * @return string
     */
    private function _extractPhone($phone)
    {
        $digits = new \Zend_Filter_Digits();
        $phone = $digits->filter($phone);
        //se começar com zero, pula o primeiro digito
        if (substr($phone, 0, 1) == '0') {
            $phone = substr($phone, 1, strlen($phone));
        }
        $originalPhone = $phone;
        $phone = preg_replace('/^(\d{2})(\d{7,9})$/', '$1-$2', $phone);
        if (is_array($phone) && count($phone) == 2) {
            list($area, $number) = explode('-', $phone);
            return implode(" ", array(
                'country' => "+55",
                'area' => (string)substr($originalPhone, 0, 2),
                'number'=> (string)substr($originalPhone, 2, 9),
            ));
        }
        return implode(" ", array(
            'country' => "+55",
            'area' => (string)substr($originalPhone, 0, 2),
            'number'=> (string)substr($originalPhone, 2, 9),
        ));
    }

    /**
     * Returns customer's CPF based on your module configuration
     *
     * @param \Magento\Sales\Model\Order $order
     * @return mixed
     */
    private function _getCustomerCpfValue(\Magento\Sales\Model\Order $order)
    {
        $customerCpfAttribute = $this->getStoreConfig('customer_cpf_attribute');
        $cpfAttributeCnf = explode('|', $customerCpfAttribute);
        $entity = reset($cpfAttributeCnf);
        $attrName = end($cpfAttributeCnf);
        $cpf = '';
        if ($entity && $attrName) {
            if (!$order->getCustomerIsGuest()) {
                $address = ($entity == 'customer') ? $order->getShippingAddress() : $order->getBillingAddress();
                $cpf = $address->getData($attrName);
                //if fail,try to get cpf from customer entity
                if (!$cpf) {
                    $customer = $order->getCustomer();
                    $cpf = $customer->getData($attrName);
                }
            }
            //for guest orders...
            if (!$cpf && $order->getCustomerIsGuest()) {
                $cpf = $order->getData($entity . '_' . $attrName);
            }
        }
        return $cpf;
    }

    /**
     * Get expire date for a order
     *
     * @param Mage_Sales_Model_Order $order
     * @return false|string
     */
    public function getExpiresAt($order) {
        $createdAt = date("Y-m-d H:i:s");
        if($order instanceof Order) {
            $createdAt = $order->getCreatedAt();
        }
        $createdAtTime = \strtotime($createdAt);
        $days = (int) $this->getStoreConfig("days_to_expires");
        if(is_numeric($days) && (int) $days > 0) {
            $createdAtTime += ($days * 86400);
        }
        else {
            return false;
        }
        return \date("c", $createdAtTime);
    }

    /**
     * Update Order by Status on PicPay api
     *
     * @param \Magento\Sales\Model\Order $order
     * @param array $consult
     * @param string $authorizationId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateOrder($order, $consult, $authorizationId)
    {
        $status = $consult["return"]["status"];
        switch ($status) {
            case "expired":
            case "refunded":
            case "chargeback":
                $this->_processRefundOrder($order, $authorizationId);
                break;
            case "paid":
            case "completed":
                $this->_processPaidOrder($order, $authorizationId);
            default: //created, analysis - don't change status order
                break;
        }
    }

    /**
     * Process Refund Order by Status on Picpay
     *
     * @param \Magento\Sales\Model\Order $order
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _processRefundOrder($order)
    {
        if($order->canUnhold()) {
            $order->unhold()->save();
        }

        if($order->canCancel()) {
            $order->cancel();
            return false;
        }

        // not can cancel, need do a creditmemo
        $service = Mage::getModel('sales/service_order', $order);

        $invoices = array();
        foreach ($order->getInvoiceCollection() as $invoice) {
            if ($invoice->canRefund()) {
                $invoices[] = $invoice;
            }
        }

        if(empty($invoices)) {
            $message = $this->__("There isn't invoice to refund on order " . $order->getIncrementId());
            if($this->getStore()->isAdmin()) {
                $this->backendSession->addError($message);
                return false;
            }
            else {
                throw new \Magento\Framework\Exception\LocalizedException($message);
            }
        }

        foreach ($invoices as $invoice) {
            $creditmemo = $service->prepareInvoiceCreditmemo($invoice)->register()->save();

            $this->transactionFactory->create()
                ->addObject($creditmemo)
                ->addObject($creditmemo->getOrder())
                ->addObject($creditmemo->getInvoice())
                ->save();
        }
    }

    /**
     * Process Paid Order by Status on Picpay
     *
     * @param \Magento\Sales\Model\Order $order
     * @param string $authorizationId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _processPaidOrder($order, $authorizationId)
    {
        if($order->getBaseTotalDue() <= 0) {
            return false;
        }

        $payment = $order->getPayment();
        $payment->setAdditionalInformation("authorizationId", $authorizationId);
        $payment->save();

        $invoice = Mage::getModel('sales/service_order', $order)
            ->prepareInvoice();

        if (!$invoice->getTotalQty()) {
            $message = $this->__("Cannot create an invoice without products.");
            if($this->getStore()->isAdmin()) {
                $this->backendSession->addError($message);
                return false;
            }
            else {
                throw new \Magento\Framework\Exception\LocalizedException($message);
            }
        }

        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
        $invoice->register();

        $invoice->getOrder()->setCustomerNoteNotify(false);
        $invoice->getOrder()->setIsInProcess(true);

        $order->addStatusHistoryComment($this->__("Order invoiced by API notification. Authorization Id: ".$authorizationId), false);

        $invoice->pay();
        $invoice->sendEmail(true);

        $transactionSave = $this->transactionFactory->create()
            ->addObject($invoice)
            ->addObject($order);

        $transactionSave->save();

        /** @var \Magento\Sales\Model\Order\Status $status */
        $status = $this->salesOrderStatusFactory->create()->loadDefaultByState("processing");
        if($status) {
            $order->setStatus($status->getStatus());
        }

        $order->save();
    }

    public function generateQrCode($dataText, $imageWidth = 200)
    {
        return null;
//        $svgTagId   = 'picpay-qrcode';
//        $saveToFile = false;
//        return QRcode::svg($dataText, $svgTagId, $saveToFile, QR_ECLEVEL_L, $imageWidth);
    }
}
