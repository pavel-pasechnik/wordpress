<?php

namespace WPFitter\Aws\Arn\S3;

use WPFitter\Aws\Arn\ArnInterface;
/**
 * @internal
 */
interface OutpostsArnInterface extends ArnInterface
{
    public function getOutpostId();
}
