<?php
namespace JiFramework\Core\Cache;

interface CacheInterface
{
  /**
   * Get cache
   * @param string $key
   * @return mixed
   */
    public function get($key);
    
    /**
     * Set cache
     * @param string $key
     * @param mixed $value
     * @param int $duration
     * @return bool
     */
    public function set($key, $value, $ttl=null);

    /**
     * Delete cache
     * @param string $key
     * @return bool
     */
    public function delete($key);

    /**
     * Check cache exists
     * @param string $key
     * @return bool
     */
    public function has($key);

    /**
     * Increment a numeric cache item's value.
     * @param string $key
     * @param int $value
     * @return bool
     */
    public function increment($key, $value=1);

    /**
     * Decrement a numeric cache item's value.
     * @param string $key
     * @param int $value
     * @return bool
     */
    public function decrement($key, $value=1);

    /**
     * Clear all cache
     * @return bool
     */
    public function clear();

    /**
     * Garbage collection to remove expired cache items.
     * @return bool
     */
    public function gc();

}


