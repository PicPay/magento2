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

namespace Picpay\Payment\Api\Data;

interface CallbackInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case.
     */
    const ENTITY_ID = 'entity_id';
    const INCREMENT_ID = 'increment_id';
    const STATUS = 'status';
    const METHOD = 'method';
    const PAYLOAD = 'payload';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * Get EntityId.
     *
     * @return int
     */
    public function getEntityId();

    /**
     * Set EntityId.
     * @param $entityId
     */
    public function setEntityId($entityId);

    /**
     * Get IncrementID.
     *
     * @return string
     */
    public function getIncrementId();

    /**
     * Set IncrementId.
     * @param $orderId
     */
    public function setIncrementId($incrementId);

    /**
     * Get Status.
     *
     * @return string
     */
    public function getStatus();

    /**
     * Set Status.
     * @param $status
     */
    public function setStatus($status);

    /**
     * Get Method.
     *
     * @return string
     */
    public function getMethod();

    /**
     * Set Method.
     * @param $method
     */
    public function setMethod($method);

    /**
     * Get Payload.
     *
     * @return string
     */
    public function getPayload();

    /**
     * Set Payload.
     * @param $payload
     */
    public function setPayload($payload);

    /**
     * Get CreatedAt.
     *
     * @return string
     */
    public function getCreatedAt();

    /**
     * Set CreatedAt.
     * @param $createdAt
     */
    public function setCreatedAt($createdAt);

    /**
     * Get CreatedAt.
     *
     * @return string
     */
    public function getUpdatedAt();

    /**
     * Set Updated At.
     * @param $updatedAt
     */
    public function setUpdatedAt($updatedAt);

    /**
     * @return \Picpay\Payment\Api\Data\CallbackExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * @param \Picpay\Payment\Api\Data\CallbackExtensionInterface $extensionAttributes
     * @return void
     */
    public function setExtensionAttributes(CallbackExtensionInterface $extensionAttributes);
}
