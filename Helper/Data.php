<?php

namespace Picpay\Payment\Helper;

use Magento\Backend\Model\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Eav\Model\ConfigFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\HTTP\Adapter\Curl;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\RefundInvoiceInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\StatusFactory;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Data extends AbstractHelper
{
    public const API_URL = "https://appws.picpay.com/ecommerce/public";
    public const MODULE_NAME = "Picpay_Payment";
    public const ONPAGE_MODE = 1;
    public const REDIRECT_MODE = 3;

    public const XML_PATH_SYSTEM_CONFIG = "payment/picpay_standard";
    public const SUCCESS_HISTORY_PATH_URL = "sales/order/history";

    public const DEFAULT_QRCODE_WIDTH = 150;

    /**
     * Store
     * @var bool|\Magento\Store\Model\Store
     */
    protected $store = false;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /** @var \Magento\Eav\Model\ConfigFactory */
    protected $eavConfigFactory;

    /** @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory */
    protected $eavResourceModelEntityAttributeCollectionFactory;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \Magento\Backend\Model\Session */
    protected $backendSession;

    /** @var \Magento\Framework\DB\TransactionFactory */
    protected $transactionFactory;

    /** @var \Magento\Sales\Model\Order\StatusFactory */
    protected $salesOrderStatusFactory;

    /** @var UrlInterface */
    protected $urlBuilder;

    /** @var ModuleListInterface */
    protected $moduleList;

    /** @var CustomerRepositoryInterface */
    protected $customerRepositoryInterface;

    /** @var \Magento\Framework\HTTP\Adapter\Curl $curl */
    protected $curl;

    /** @var \Magento\Sales\Api\RefundInvoiceInterface */
    protected $invoiceRefunder;

    /** @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender */
    protected $invoiceSender;

    /** @var \Magento\Framework\Message\ManagerInterface */
    protected $messageManager;

    /** @var \Magento\Sales\Model\Service\InvoiceService */
    protected $invoiceService;

    /** @var OrderInterface  */
    protected $order;

    /** @var CreditmemoFactory  */
    protected $creditmemoFactory;

    /** @var CreditmemoService  */
    protected $creditmemoService;

    /** @var Invoice  */
    protected $invoice;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        ConfigFactory $eavConfigFactory,
        CollectionFactory $eavResourceModelEntityAttributeCollectionFactory,
        LoggerInterface $logger,
        Session $backendSession,
        TransactionFactory $transactionFactory,
        StatusFactory $salesOrderStatusFactory,
        UrlInterface $urlBuilder,
        ModuleListInterface $moduleList,
        CustomerRepositoryInterface $customerRepositoryInterface,
        Curl $curl,
        RefundInvoiceInterface $refundInvoice,
        InvoiceSender $invoiceSender,
        InvoiceService $invoiceService,
        ManagerInterface $messageManager,
        CreditmemoFactory $creditmemoFactory,
        CreditmemoService $creditmemoService,
        Invoice $invoice,
        OrderInterface $order
    ) {
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
        $this->invoiceRefunder = $refundInvoice;
        $this->invoiceSender = $invoiceSender;
        $this->invoiceService = $invoiceService;
        $this->messageManager = $messageManager;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->creditmemoService = $creditmemoService;
        $this->invoice = $invoice;
        $this->order = $order;

        if (is_null($this->store)) {
            $this->store = $this->storeManager->getStore();
        }
    }

    public function getStoreConfig(string $path): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_SYSTEM_CONFIG . '/' . $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get object store
     */
    public function getStore()
    {
        if (!$this->store) {
            $this->store = $this->storeManager->getStore();
        }
        return $this->store;
    }

    public function isActive(): bool
    {
        return (bool) $this->getStoreConfig('active');
    }

    public function getCheckoutMode(): string
    {
        return $this->getStoreConfig("mode");
    }

    public function isOnpageMode(): bool
    {
        return !$this->isRedirectMode();
    }

    public function isRedirectMode(): bool
    {
        return ($this->getCheckoutMode() == self::REDIRECT_MODE);
    }

    public function getToken(): string
    {
        return $this->getStoreConfig("token");
    }

    public function getSellerToken(): string
    {
        return $this->getStoreConfig("seller_token");
    }

    /**
     * Get qrcode width info
     *
     * @return string
     */
    public function getQrcodeInfoWidth(): string
    {
        $value = $this->getStoreConfig("qrcode_info_width");
        return $value ?: self::DEFAULT_QRCODE_WIDTH;
    }

    /**
     * Get qrcode width info
     *
     * @return string
     */
    public function getQrcodeOnpageWidth()
    {
        $value = $this->getStoreConfig("onpage_width");
        return $value ?: self::DEFAULT_QRCODE_WIDTH;
    }

    /**
     * Get module's version
     *
     * @return mixed
     */
    public function getVersion()
    {
        return " - v" . $this->moduleList
                ->getOne(self::MODULE_NAME)['setup_version'];
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
    public function isCurrentlySecure(): bool
    {
        return (bool) $this->storeManager->getStore()->isCurrentlySecure();
    }

    /**
     * @return UrlInterface
     */
    public function getUrlBuilder(): UrlInterface
    {
        return $this->urlBuilder;
    }

    /**
     * Get URL to return to store
     * @var integer|string $orderId
     */
    public function getReturnUrl($orderId = false): string
    {
        return $this->urlBuilder->getUrl(
            self::SUCCESS_HISTORY_PATH_URL,
            [
                "order_id" => $orderId,
                "_secure" => $this->isCurrentlySecure()
            ]
        );
    }

    /**
     * Get URL to return to store
     */
    public function getCallbackUrl(): string
    {
        return $this->urlBuilder->getUrl(
            'picpay/notification/',
            ["_secure" => $this->isCurrentlySecure(), "isAjax" => 1]
        );
    }

    /**
     * Validate a HTTP Request Authorization
     *
     * @param \Magento\Framework\App\Request\Http $request
     * @return bool
     * @throws \Exception
     */
    public function validateAuth($request): bool
    {
        $token = $request->getHeader('x-seller-token');
        return ($this->getSellerToken() && $token == $this->getSellerToken());
    }

    public function loadOrder(string $incrementId): OrderInterface
    {
        return $this->order->loadByIncrementId($incrementId);
    }

    /**
     * Log function to debug
     *
     * @param mixed
     */
    public function log(string $data): void
    {
        if ($this->getStoreConfig("debug")) {
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
        $success = 0;
        $message = '';

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
                'timeout' => $timeout
            ]);

            $this->log("JSON sent to PicPay API. URL: " . $url);
            $this->log((is_array($fields) ? \json_encode($fields) : $fields));

            $this->curl->write($type,
                $url,
                '1.1',
                $headers,
                (is_array($fields) ? \json_encode($fields) : $fields)
            );

            $response = $this->curl->read();

            $this->log("JSON Response from PicPay API");
            $this->log((string) $response);

            $httpCode = $this->extractCode($response);

            if ($httpCode != 200 && $httpCode == 201) {
                $message = $response;
            } else {
                $response = $this->extractBody($response);
                $success = 1;
                $message = \json_decode(trim($response), true);
            }

        } catch (\Exception $e) {
            $this->log("ERROR on requesting API: " . $e->getMessage());
            $this->logger->critical($e);
            $message = $e->getMessage();
        }

        return [
            'success' => $success,
            'return' => $message
        ];
    }

    public function extractCode(string $response): int
    {
        preg_match("|^HTTP/[\d\.x]+ (\d+)|", $response, $m);
        if (isset($m[1])) {
            return (int) $m[1];
        }
        return 500;
    }

    public function extractBody(string $response): string
    {
        $parts = preg_split('|(?:\r\n){2}|m', $response, 2);
        if (isset($parts[1])) {
            return $parts[1];
        }
        return '';
    }

    /**
     * Get buyer object from Order
     *
     * @param $order
     * @param null|\Magento\Quote\Model\Quote $quote
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getBuyer($order, $quote = null)
    {
        /** @var \Magento\Payment\Gateway\Data\Order\AddressAdapter $billingAddress */
        $billingAddress = $order->getBillingAddress();
        $taxvat = false;

        $customerId = $order->getCustomerId();
        if ($customerId) {
            $customer = $this->customerRepositoryInterface->getById($customerId);
            if ($customer && $customer->getId()) {
                $taxvat = $customer->getTaxvat();
            }
        }

        if (!$taxvat && $quote) {
            /** @var \Magento\Quote\Model\Quote $quote */
            $addressObj = $quote->getBillingAddress();
            if ($addressObj && $addressObj->getId()) {
                $taxvat = $addressObj->getVatId();
            }
        }

        $buyerFirstname = $billingAddress->getFirstname();
        $buyerLastname = $billingAddress->getLastname();
        $buyerDocument = $this->_formatTaxVat($taxvat);
        $buyerEmail = $billingAddress->getEmail();
        $buyerPhone = $this->extractPhone($billingAddress->getTelephone());

        return [
            "firstName" => $buyerFirstname,
            "lastName" => $buyerLastname,
            "document" => $buyerDocument,
            "email" => $buyerEmail,
            "phone" => $buyerPhone
        ];
    }

    /**
     * Returns Tax Vat formatted
     *
     * @param string $taxvat
     * @return string
     */

    private function _formatTaxVat($taxvat){
        $formatado = substr($taxvat, 0, 3) . '.';
        $formatado .= substr($taxvat, 3, 3) . '.';
        $formatado .= substr($taxvat, 6, 3) . '-';
        $formatado .= substr($taxvat, 9, 2) . '';

        return $formatado;
    }

    /**
     * Extracts phone area code and returns phone number
     *
     * @param string $phone
     * @return string
     */
    private function extractPhone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        //se começar com zero, pula o primeiro digito
        if (substr($phone, 0, 1) == '0') {
            $phone = substr($phone, 1, strlen($phone));
        }
        $originalPhone = $phone;
        $phone = preg_replace('/^(\d{2})(\d{7,9})$/', '$1-$2', $phone);
        if (is_array($phone) && count($phone) == 2) {
            list($area, $number) = explode('-', $phone);
            return implode(" ", [
                'country' => "+55",
                'area' => (string)substr($originalPhone, 0, 2),
                'number' => (string)substr($originalPhone, 2, 9),
            ]);
        }
        return implode(" ", [
            'country' => "+55",
            'area' => (string)substr($originalPhone, 0, 2),
            'number' => (string)substr($originalPhone, 2, 9),
        ]);
    }

    /**
     * Get expire date for a order
     *
     * @param Mage_Sales_Model_Order $order
     * @return false|string
     */
    public function getExpiresAt($order)
    {
        $createdAt = date("Y-m-d H:i:s");
        if ($order instanceof Order) {
            $createdAt = $order->getCreatedAt();
        }
        $createdAtTime = \strtotime($createdAt);

        $hours = (int)$this->getStoreConfig("hours_to_expires");

        if (is_numeric($hours) && (int)$hours > 0) {
            $createdAtTime += ($hours * 3600);
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
    protected function _processRefundOrder($order, $authorizationId)
    {
        if ($order->canUnhold()) {
            $order->unhold();
        }

        if ($order->canCancel()) {
            $order->cancel();
        }

        $invoiceIncrementId = false;
        foreach ($order->getInvoiceCollection() as $invoice) {
            $invoiceIncrementId = $invoice->getIncrementId();
        }

        if ($invoiceIncrementId) {
            $invoiceObj = $this->invoice->loadByIncrementId($invoiceIncrementId);
            $creditMemo = $this->creditmemoFactory->createByOrder($order);

            $creditMemo->setInvoice($invoiceObj);
            $this->creditmemoService->refund($creditMemo);

        }
        $order->save();
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
        if ($order->getBaseTotalDue() <= 0) {
            return false;
        }

        $payment = $order->getPayment();
        $payment->setAdditionalInformation("authorizationId", $authorizationId);
        $payment->save();

        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        $invoice = $this->invoiceService->prepareInvoice($order);

        if (!$invoice) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('We can\'t save the invoice right now.')
            );
        }
        if (!$invoice->getTotalQty()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('You can\'t create an invoice without products.')
            );
        }

        $invoice->setRequestedCaptureCase(
            \Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE
        );

        $invoice->register();

        $invoice->getOrder()->setCustomerNoteNotify(false);
        $invoice->getOrder()->setIsInProcess(true);

        $order->addStatusHistoryComment(
            __("Order invoiced by API notification. Authorization Id: " . $authorizationId),
            false
        );

        $invoice->pay();
        $invoice->setEmailSent(true);

        $transactionSave = $this->transactionFactory->create()
            ->addObject($invoice)
            ->addObject($order);

        $transactionSave->save();

        try {
            $this->invoiceSender->send($invoice);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('We can\'t send the invoice email right now.'));
        }

        /** @var \Magento\Sales\Model\Order\Status $status */
        $status = $this->salesOrderStatusFactory->create()->loadDefaultByState("processing");
        if ($status) {
            $order->setStatus($status->getStatus());
        }

        $order->save();
    }

    public function generateQrCode($dataText, string $imageWidth = '200', string $style = ""): string
    {
        if (is_array($dataText)) {
            $dataText = $dataText['base64'];
        }
        return '<img src="' . $dataText . '" width="' . $imageWidth . '" style="' . $style . '"/>';
    }
}
