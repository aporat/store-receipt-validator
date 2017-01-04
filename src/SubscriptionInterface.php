<?php

namespace ReceiptValidator;

/**
 * Interface SubscriptionInterface
 * @package ReceiptValidator
 */
interface SubscriptionInterface
{
  /**
   * @return mixed date represented as milliseconds
   */
  public function getExpiresDate();
}