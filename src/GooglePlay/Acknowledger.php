<?php

namespace ReceiptValidator\GooglePlay;

/**
 * Class Acknowledger.
 */
class Acknowledger
{
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
     * Acknowledger constructor.
     *
     * @param \Google_Service_AndroidPublisher $googleServiceAndroidPublisher
     * @param string                           $packageName
     * @param string                           $purchaseToken
     * @param string                           $productId
     */
    public function __construct(
        \Google_Service_AndroidPublisher $googleServiceAndroidPublisher,
        $packageName,
        $productId,
        $purchaseToken
    ) {
        $this->androidPublisherService = $googleServiceAndroidPublisher;
        $this->packageName = $packageName;
        $this->purchaseToken = $purchaseToken;
        $this->productId = $productId;
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
                    $subscriptionPurchase = $this->androidPublisherService->purchases_subscriptions->get(
                        $this->packageName,
                        $this->productId,
                        $this->purchaseToken
                    );
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
                    throw new \RuntimeException(
                        \sprintf(
                            'Invalid type provided : %s expected %s',
                            $type,
                            implode(',', [self::PRODUCT, self::SUBSCRIPTION])
                        )
                    );
            }

            return true;
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
