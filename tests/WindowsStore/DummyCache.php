<?php

namespace ReceiptValidator\Tests\WindowsStore;

use ReceiptValidator\WindowsStore\CacheInterface;

class DummyCache implements CacheInterface
{
    protected $cache = [];

    public function get($key)
    {
        return $this->cache[$key] ?? null;
    }

    public function put($key, $value, $minutes)
    {
        $this->cache[$key] = $value;
    }
}
