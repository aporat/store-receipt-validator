<?php

namespace ReceiptValidator\GooglePlay;

use ReceiptValidator\GooglePlay\Exception\AlreadyAcknowledgeException;
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
     * @var \Google_Service_AndroidPublisher
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
     * @param \Google_Service_AndroidPublisher $googleServiceAndroidPublisher
     * @param string                           $packageName
     * @param string                           $purchaseToken
     * @param string                           $productId
     * @param string                           $strategy
     *
     * @throws RunTimeException
     */
    public function __construct(
        \Google_Service_AndroidPublisher $googleServiceAndroidPublisher,
        $packageName,
        $productId,
        $purchaseToken,
        $strategy = self::ACKNOWLEDGE_STRATEGY_EXPLICIT
    ) {
        if (!\in_array($strategy, [self::ACKNOWLEDGE_STRATEGY_EXPLICIT, self::ACKNOWLEDGE_STRATEGY_IMPLICIT])) {
            throw new RuntimeException(\sprintf('Invalid strategy provided %s', $strategy));
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
     * @throws RunTimeException
     *
     * @return bool
     */
    public function acknowledge(string $type = self::SUBSCRIPTION, string $developerPayload = '')
    {
        switch ($type) {
            case self::SUBSCRIPTION:
                $subscriptionPurchase = $this->androidPublisherService->purchases_subscriptions->get(
                    $this->packageName,
                    $this->productId,
                    $this->purchaseToken
                );

                if ($this->strategy === self::ACKNOWLEDGE_STRATEGY_EXPLICIT
                    && $subscriptionPurchase->getAcknowledgementState() === 1) {
                    throw AlreadyAcknowledgeException::fromSubscriptionPurchase($subscriptionPurchase);
                }

                if ($subscriptionPurchase->getAcknowledgementState() != 1) {
                    $this->androidPublisherService->purchases_subscriptions->acknowledge(
                        $this->packageName,
                        $this->productId,
                        $this->purchaseToken,
                        new \Google_Service_AndroidPublisher_SubscriptionPurchasesAcknowledgeRequest(
                            ['developerPayload' => $developerPayload]
                        )
                    );
                }
                break;
            case self::PRODUCT:
                $productPurchase = $this->androidPublisherService->purchases_products->get(
                    $this->packageName,
                    $this->productId,
                    $this->purchaseToken
                );

                if ($this->strategy === self::ACKNOWLEDGE_STRATEGY_EXPLICIT
                    && $productPurchase->getAcknowledgementState() === 1) {
                    throw AlreadyAcknowledgeException::fromProductPurchase($productPurchase);
                }

                if ($productPurchase->getAcknowledgementState() != 1) {
                    $this->androidPublisherService->purchases_products->acknowledge(
                        $this->packageName,
                        $this->productId,
                        $this->purchaseToken,
                        new \Google_Service_AndroidPublisher_ProductPurchasesAcknowledgeRequest(
                            ['developerPayload' => $developerPayload]
                        )
                    );
                }
                break;
            default:
                throw new RuntimeException(
                    \sprintf(
                        'Invalid type provided : %s expected %s',
                        $type,
                        implode(',', [self::PRODUCT, self::SUBSCRIPTION])
                    )
                );
        }

        return true;
    }
}