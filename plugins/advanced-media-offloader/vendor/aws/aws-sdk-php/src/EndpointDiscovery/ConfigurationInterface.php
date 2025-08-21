<?php

namespace WPFitter\Aws\EndpointDiscovery;

/**
 * Provides access to endpoint discovery configuration options:
 * 'enabled', 'cache_limit'
 * @internal
 */
interface ConfigurationInterface
{
    /**
     * Checks whether or not endpoint discovery is enabled.
     *
     * @return bool
     */
    public function isEnabled();
    /**
     * Returns the cache limit, if available.
     *
     * @return string|null
     */
    public function getCacheLimit();
    /**
     * Returns the configuration as an associative array
     *
     * @return array
     */
    public function toArray();
}
