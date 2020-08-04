<?php

namespace ReceiptValidator\GooglePlay;

use Exception;
use Google_Service_AndroidPublisher;
use ReceiptValidator\RunTimeException;

/**
 * Class Acknowledger.
 */
class Acknowledger
{
    // Do acknowledge only in case if it have not done
    const ACKNOWLEDGE_STRATEGY_IMPLICIT = 'strategy_implicit';
    // Try to do acknowledge directly (exception will be returned in case when acknowledge already was done)
    const ACKNOWLEDGE_STRATEGY_EXPLICIT = 'strategy_explicit';

    const SUBSCRIPTION = 'SUBSCRIPTION';
    const PRODUCT = 'PRODUCT';

    /**
     * @var Google_Service_AndroidPublisher
     */
    protected $androidPublisherService;
    /**
     * @var string
     */
    protected $packageName;
    /**
     * @var string
     */
    protected $purchaseToken;
    /**
     * @var string
     */
    protected $productId;
    /**
     * @var string
     */
    protected $strategy;

    /**
     * Acknowledger constructor.
     *
     * @param Google_Service_AndroidPublisher $googleServiceAndroidPublisher
     * @param string                          $packageName
     * @param string                          $purchaseToken
     * @param string                          $productId
     * @param string                          $strategy
     *
     * @throws RunTimeException
     */
    public function __construct(
        Google_Service_AndroidPublisher $googleServiceAndroidPublisher,
        $packageName,
        $productId,
        $purchaseToken,
        $strategy = self::ACKNOWLEDGE_STRATEGY_EXPLICIT
    ) {
        if (!in_array($strategy, [self::ACKNOWLEDGE_STRATEGY_EXPLICIT, self::ACKNOWLEDGE_STRATEGY_IMPLICIT])) {
            throw new RuntimeException(sprintf('Invalid strategy provided %s', $strategy));
        }

        $this->androidPublisherService = $googleServiceAndroidPublisher;
        $this->packageName = $packageName;
        $this->purchaseToken = $purchaseToken;
        $this->productId = $productId;
        $this->strategy = $strategy;
    }

    /**
     * @param string $type
     * @param string $developerPayload
     *
     * @return bool
     */
    public function acknowledge(string $type = self::SUBSCRIPTION, string $developerPayload = '')
    {
        try {
            switch ($type) {
                case self::SUBSCRIPTION:
                    if ($this->strategy === self::ACKNOWLEDGE_STRATEGY_EXPLICIT) {
                        // Here exception might be thrown as previously, so no BC break here
                        $this->androidPublisherService->purchases_subscriptions->acknowledge(
                            $this->packageName,
                            $this->productId,
                            $this->purchaseToken,
                            new \Google_Service_AndroidPublisher_SubscriptionPurchasesAcknowledgeRequest(
                                ['developerPayload' => $developerPayload]
                            )
                        );
                    } elseif ($this->strategy === self::ACKNOWLEDGE_STRATEGY_IMPLICIT) {
                        $subscriptionPurchase = $this->androidPublisherService->purchases_subscriptions->get(
                            $this->packageName,
                            $this->productId,
                            $this->purchaseToken
                        );

                        if ($subscriptionPurchase->getAcknowledgementState() !== AbstractResponse::ACKNOWLEDGEMENT_STATE_DONE) {
                            $this->androidPublisherService->purchases_subscriptions->acknowledge(
                                $this->packageName,
                                $this->productId,
                                $this->purchaseToken,
                                new \Google_Service_AndroidPublisher_SubscriptionPurchasesAcknowledgeRequest(
                                    ['developerPayload' => $developerPayload]
                                )
                            );
                        }
                    }
                    break;
                case self::PRODUCT:
                    if ($this->strategy === self::ACKNOWLEDGE_STRATEGY_EXPLICIT) {
                        // Here exception might be thrown as previously, so no BC break here
                        $this->androidPublisherService->purchases_products->acknowledge(
                            $this->packageName,
                            $this->productId,
                            $this->purchaseToken,
                            new \Google_Service_AndroidPublisher_ProductPurchasesAcknowledgeRequest(
                                ['developerPayload' => $developerPayload]
                            )
                        );
                    } elseif ($this->strategy === self::ACKNOWLEDGE_STRATEGY_IMPLICIT) {
                        $productPurchase = $this->androidPublisherService->purchases_products->get(
                            $this->packageName,
                            $this->productId,
                            $this->purchaseToken
                        );

                        if ($productPurchase->getAcknowledgementState() !== AbstractResponse::ACKNOWLEDGEMENT_STATE_DONE) {
                            $this->androidPublisherService->purchases_products->acknowledge(
                                $this->packageName,
                                $this->productId,
                                $this->purchaseToken,
                                new \Google_Service_AndroidPublisher_ProductPurchasesAcknowledgeRequest(
                                    ['developerPayload' => $developerPayload]
                                )
                            );
                        }
                    }
                    break;
                default:
                    throw new \RuntimeException(
                        sprintf(
                            'Invalid type provided : %s expected %s',
                            $type,
                            implode(',', [self::PRODUCT, self::SUBSCRIPTION])
                        )
                    );
            }

            return true;
        } catch (Exception $e) {
            throw new \RuntimeException($e->getCode(), $e->getCode(), $e);
        }
    }
}
