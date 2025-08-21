<?php

namespace WPFitter\Aws;

use WPFitter\Psr\Http\Message\ResponseInterface;
/** @internal */
interface ResponseContainerInterface
{
    /**
     * Get the received HTTP response if any.
     *
     * @return ResponseInterface|null
     */
    public function getResponse();
}
