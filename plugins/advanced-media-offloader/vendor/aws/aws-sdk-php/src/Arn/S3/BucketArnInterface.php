<?php

namespace WPFitter\Aws\Arn\S3;

use WPFitter\Aws\Arn\ArnInterface;
/**
 * @internal
 */
interface BucketArnInterface extends ArnInterface
{
    public function getBucketName();
}
