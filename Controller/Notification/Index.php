<?php

namespace Picpay\Payment\Controller\Notification;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Event\Magento;
use Picpay\Payment\Api\CallbackRepositoryInterface;
use Picpay\Payment\Api\Data\CallbackInterface;
use Picpay\Payment\Helper\Data;
use Picpay\Payment\Model\CallbackFactory;
use Picpay\Payment\Model\Ui\ConfigProvider;

class Index extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \Magento\Sales\Model\OrderFactory */
    protected $salesOrderFactory;

    /** @var Data */
    protected $paymentHelper;

    /** @var \Magento\Framework\Controller\Result\JsonFactory */
    protected $resultJsonFactory;

    /** @var \Magento\Framework\Serialize\Serializer\Json  */
    protected $serializer;

    /** @var ManagerInterface */
    protected $eventManager;

    /** @var CallbackRepositoryInterface  */
    protected $callbackRepository;

    /** @var CallbackFactory  */
    protected $callbackFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Model\OrderFactory $salesOrderFactory,
        Data $paymentHelper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        ManagerInterface $eventManager,
        CallbackFactory $callbackFactory,
        CallbackRepositoryInterface $callbackRepository
    ) {
        $this->logger = $logger;
        $this->salesOrderFactory = $salesOrderFactory;
        $this->paymentHelper = $paymentHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->serializer = $serializer;
        $this->eventManager = $eventManager;
        $this->callbackFactory = $callbackFactory;
        $this->callbackRepository = $callbackRepository;
        parent::__construct(
            $context
        );
    }

    public function getHelper(): Data
    {
        return $this->paymentHelper;
    }

    /**
     * Public toJson response
     *
     * @param array $data
     * @param int $statusCode
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function toJson($data = array(), $statusCode = 200)
    {
        return $this->getResponse()
            ->setHeader('Content-type', 'application/json')
            ->setBody($this->serializer->serialize($data))
            ->setHttpResponseCode($statusCode);
    }

    /**
     * Normalize a request params based on content-type and methods
     *
     * @param \Magento\Framework\App\RequestInterface $request Request with data (raw body, json, form data, etc)
     * @param array $methods Accepted methods to normalize data
     * @return \Magento\Framework\App\ResponseInterface
     * @throws \Exception
     */
    protected function normalizeParams($request, $methods = array('PUT', 'POST'))
    {
        if (in_array($request->getMethod(), $methods) && 'application/json' == $request->getHeader('Content-Type')) {
            if (false !== ($body = $request->getContent())) {
                $this->getHelper()->log((string) $body);
                try {
                    $body = str_replace("\t","",$body);
                    $body = str_replace("\r","",$body);
                    $body = str_replace("\n","",$body);
                    $data = $this->serializer->unserialize( $body );
                } catch (\Exception $exception) {
                    $this->logger->critical($exception);
                    throw new \Exception($exception->getMessage());
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
        $resultPage = $this->resultJsonFactory->create();
        $statusCode = 400;
        try {
            $request = $this->normalizeParams($this->getRequest());
            $requestParams = json_encode($request->getParams(), true);
            $this->logger->debug($requestParams);

            $referenceId = $request->get("referenceId");
            $authorizationId = $request->get("authorizationId");

            $response = ['success' => false];
            $resultPage->setData($response);

            if (!$this->getHelper()->isNotificationEnabled()) {
                throw new \Exception('Notifications are disabled', 403);
            }

            if (!$this->getHelper()->validateAuth($this->getRequest())) {
                throw new \Exception('Invalid auth', 401);
            }

            if (!$referenceId) {
                throw new \Exception('Invalid referenceId', 422);
            }

            $order = $this->salesOrderFactory->create()->loadByIncrementId($referenceId);
            if (!$order || !$order->getId()) {
                throw new \Exception('Order not found', 404);
            }

            $return = $this->consultRequest($order);
            if (isset($return["return"]["status"])) {
                $response = ['success' => true];
                $resultPage->setData($response);
                $this->getHelper()->updateOrder($order, $return, $authorizationId);
                $statusCode = 200;
            }

            $this->saveCallback($requestParams, $order->getIncrementId(), $statusCode);

        } catch (\Exception $e) {
            $this->logger->critical($e);
            $statusCode = $e->getCode() ?: 500;
        }

        return $resultPage->setHttpResponseCode($statusCode);
    }

    protected function saveCallback(string $request, string $incrementId, int $statusCode): void
    {
        try {
            /** @var CallbackInterface $callback */
            $callback = $this->callbackFactory->create();
            $callback->setMethod(ConfigProvider::CODE);
            $callback->setIncrementId($incrementId);
            $callback->setPayload($request);
            $callback->setStatus($statusCode);
            $this->callbackRepository->save($callback);
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
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
            [],
            "GET"
        );

        if (isset($result['success'])) {
            return $result;
        }
        return false;
    }


    /**
     * Create exception in case CSRF validation failed.
     * Return null if default exception will suffice.
     *
     * @param RequestInterface $request
     *
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * Perform custom request validation.
     * Return null if default validation is needed.
     *
     * @param RequestInterface $request
     *
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): bool
    {
        return true;
    }

}
