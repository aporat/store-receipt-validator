<?php

declare(strict_types=1);

namespace ReceiptValidator\Exceptions;

use Exception;

/**
 * The primary exception thrown when a receipt validation error occurs.
 *
 * This class serves as the base exception for all validation-related failures
 * within the library, allowing developers to catch a single, consistent

 * exception type for any validation issues.
 */
class ValidationException extends Exception
{
}
