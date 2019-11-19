<?php

namespace ReceiptValidator\GooglePlay;

/**
 * Class AbstractResponse.
 */
abstract class AbstractResponse
{
    const CONSUMPTION_STATE_YET_TO_BE_CONSUMED = 0;
    const CONSUMPTION_STATE_CONSUMED = 1;
    const PURCHASE_STATE_PURCHASED = 0;
    const PURCHASE_STATE_CANCELED = 1;
    const ACKNOWLEDGEMENT_STATE_YET_TO_BE = 0;
    const ACKNOWLEDGEMENT_STATE_DONE = 1;

    /**
     * @var \Google_Service_AndroidPublisher_ProductPurchase|\Google_Service_AndroidPublisher_SubscriptionPurchase
     */
    protected $response;

    /**
     * Constructor.
     *
     * @param \Google_Service_AndroidPublisher_ProductPurchase|\Google_Service_AndroidPublisher_SubscriptionPurchase $response
     */
    public function __construct($response)
    {
        $this->response = $response;
    }

    /**
     * @return array|string
     */
    public function getDeveloperPayload()
    {
        return $this->response->getDeveloperPayload();
    }

    /**
     * @return int
     */
    public function getAcknowledgementState()
    {
        return $this->response->acknowledgementState;
    }

    /**
     * @return bool
     */
    public function isAcknowledged()
    {
        return $this->response->acknowledgementState === static::ACKNOWLEDGEMENT_STATE_DONE;
    }

    /**
     * @return string
     */
    public function getKind()
    {
        return $this->response->kind;
    }

    /**
     * @return \Google_Service_AndroidPublisher_ProductPurchase|\Google_Service_AndroidPublisher_SubscriptionPurchase
     */
    public function getRawResponse()
    {
        return $this->response;
    }
}
