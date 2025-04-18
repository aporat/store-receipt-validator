<?php

namespace ReceiptValidator\AppleAppStore;

use GuzzleHttp\Client as HttpClient;
use InvalidArgumentException;

class Validator
{
    public const string ENDPOINT_SANDBOX = 'https://api.storekit.itunes.apple.com';
    public const string ENDPOINT_PRODUCTION = 'https://api.storekit-sandbox.itunes.apple.com';
}
