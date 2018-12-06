<?php

namespace ReceiptValidator\WindowsStore;

interface CacheInterface
{
    /**
     * Retrieve an item from the cache by key. If the key is not found, null
     * should be returned.
     *
     * @param  string $key
     * @return mixed
     */
    public function get($key);

    /**
     * Store an item in the cache for a given number of minutes, where 0 minutes
     * means forever.
     *
     * @param  string $key
     * @param  mixed $value
     * @param  int $minutes
     * @return void
     */
    public function put($key, $value, $minutes);
}
