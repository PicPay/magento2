<?php
/**
 *
 *
 *
 *
 *
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Picpay
 * @package     Picpay_Payment
 *
 *
 */

namespace Picpay\Payment\Model\ResourceModel;

use Picpay\Payment\Model\CallbackFactory;
use Picpay\Payment\Api\Data\CallbackInterfaceFactory;
use Picpay\Payment\Api\Data\CallbackSearchResultsInterfaceFactory;
use Picpay\Payment\Api\CallbackRepositoryInterface;
use Picpay\Payment\Model\ResourceModel\Callback as ResourceCallback;
use Picpay\Payment\Model\ResourceModel\Callback\CollectionFactory as CallbackCollectionFactory;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class CallbackRepository implements CallbackRepositoryInterface
{

    /** @var ResourceCallback  */
    protected $resource;

    /** @var CallbackFactory  */
    protected $callbackFactory;

    /** @var CallbackCollectionFactory  */
    protected $callbackCollectionFactory;

    /** @var CallbackSearchResultsInterfaceFactory  */
    protected $searchResultsFactory;

    /** @var CallbackInterfaceFactory  */
    protected $dataCallbackFactory;

    /** @var JoinProcessorInterface  */
    protected $extensionAttributesJoinProcessor;

    /** @var CollectionProcessorInterface  */
    private $collectionProcessor;

    /**
     * @param ResourceCallback $resource
     * @param CallbackFactory $callbackFactory
     * @param CallbackInterfaceFactory $dataCallbackFactory
     * @param CallbackCollectionFactory $callbackCollectionFactory
     * @param CallbackSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     */
    public function __construct(
        ResourceCallback $resource,
        CallbackFactory $callbackFactory,
        CallbackInterfaceFactory $dataCallbackFactory,
        CallbackCollectionFactory $callbackCollectionFactory,
        CallbackSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor
    ) {
        $this->resource = $resource;
        $this->callbackFactory = $callbackFactory;
        $this->callbackCollectionFactory = $callbackCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataCallbackFactory = $dataCallbackFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id) {
        /** @var \Picpay\Payment\Model\Callback $callback */
        $callback = $this->callbackFactory->create();
        $this->resource->load($callback, $id);
        if (!$callback->getId()) {
            throw new NoSuchEntityException(__('Item with id "%1" does not exist.', $id));
        }
        return $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \Picpay\Payment\Api\Data\CallbackInterface $callback
    ) {
        try {
            $callback = $this->resource->save($callback);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the callback info: %1',
                $exception->getMessage()
            ));
        }
        return $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->callbackCollectionFactory->create();

        $this->extensionAttributesJoinProcessor->process(
            $collection,
            \Picpay\Payment\Api\Data\CallbackInterface::class
        );

        $this->collectionProcessor->process($criteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }
}
