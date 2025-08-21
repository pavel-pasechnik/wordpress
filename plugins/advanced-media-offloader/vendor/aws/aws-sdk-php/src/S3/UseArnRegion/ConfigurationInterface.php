<?php

namespace WPFitter\Aws\S3\UseArnRegion;

/** @internal */
interface ConfigurationInterface
{
    /**
     * Returns whether or not to use the ARN region if it differs from client
     *
     * @return bool
     */
    public function isUseArnRegion();
    /**
     * Returns the configuration as an associative array
     *
     * @return array
     */
    public function toArray();
}
