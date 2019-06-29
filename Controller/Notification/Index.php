<?php

namespace Picpay\Payment\Controller\Notification;

use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Event\Magento;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $salesOrderFactory;

    /**
     * @var \Picpay\Payment\Helper\Data
     */
    protected $paymentHelper;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Model\OrderFactory $salesOrderFactory,
        \Picpay\Payment\Helper\Data $paymentHelper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    )
    {
        $this->logger = $logger;
        $this->salesOrderFactory = $salesOrderFactory;
        $this->paymentHelper = $paymentHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct(
            $context
        );
    }

    /**
     * Retrieves the helper
     *
     * @param string Helper alias
     * @return \Picpay\Payment\Helper\Data
     */
    public function getHelper()
    {
        return $this->paymentHelper;
    }

    /**
     * Protected toJson response
     *
     * @param array $data Data to be json encoded
     * @param int $statusCode HTTP response status code
     * @return \Zend_Controller_Response_Abstract
     * @throws \Zend_Controller_Exception
     */
    protected function _toJson($data = array(), $statusCode = 200)
    {
        return $this
            ->getResponse()
            ->setHeader('Content-type', 'application/json')
            ->setBody(\Zend_Json::encode($data))
            ->setHttpResponseCode($statusCode);
    }

    /**
     * Public toJson response
     *
     * @param array $data
     * @param int $statusCode
     * @return \Zend_Controller_Response_Abstract
     */
    public function toJson($data = array(), $statusCode = 200)
    {
        return $this->_toJson($data, $statusCode);
    }

    /**
     * Normalize a request params based on content-type and methods
     *
     * @param \Zend_Controller_Request_Http $request Request with data (raw body, json, form data, etc)
     * @param array $methods Accepted methods to normalize data
     * @return \Zend_Controller_Request_Http
     * @throws \Zend_Controller_Request_Exception
     * @throws \Zend_Json_Exception
     */
    protected function _normalizeParams($request, $methods = array('PUT', 'POST'))
    {
        if (in_array($request->getMethod(), $methods) && 'application/json' == $request->getHeader('Content-Type')) {
            if (false !== ($body = $request->getContent())) {
                $this->getHelper()->log($body);
                try {
                    $body = str_replace("\t","",$body);
                    $body = str_replace("\r","",$body);
                    $body = str_replace("\n","",$body);
                    $data = \Zend_Json::decode( $body );
                }
                catch (Exception $exception) {
                    $this->logger->critical($exception);
                    throw new \Zend_Json_Exception($exception->getMessage());
                }
                $request->setParams($data);
            }
        }
        return $request;
    }

    /**
     * Action to handling notifications from PicPay
     */
    public function execute()
    {
        $request = $this->_normalizeParams($this->getRequest());
        $this->logger->debug(print_r($request->getParams(), true));

        $referenceId = $request->get("referenceId");
        $authorizationId = $request->get("authorizationId");
        $resultPage = $this->resultJsonFactory->create();

        if (!$referenceId) {
            return $resultPage->setHttpResponseCode(422);
        }

        $order = $this->salesOrderFactory->create()->loadByIncrementId($referenceId);

        if (!$order || !$order->getId()) {
            return $resultPage->setHttpResponseCode(422);
        }

        try {
            $return = $this->consultRequest($order);
            if (isset($return["return"]["status"])) {
                $this->getHelper()->updateOrder($order, $return, $authorizationId);
            } else {
                return $resultPage->setHttpResponseCode(400);
            }
        } catch (Exception $e) {
            $this->logger->critical($e);
            $resultPage->setHttpResponseCode(422);
        }

        return $resultPage;
    }

    /**
     * Consult transaction via API
     *
     * @param Order $order
     * @return bool|mixed|string
     */
    public function consultRequest($order)
    {
        $result = $this->getHelper()->requestApi(
            $this->getHelper()->getApiUrl("/payments/{$order->getIncrementId()}/status"),
            array(),
            "GET"
        );
        if(isset($result['success'])) {
            return $result;
        }
        return false;
    }
}