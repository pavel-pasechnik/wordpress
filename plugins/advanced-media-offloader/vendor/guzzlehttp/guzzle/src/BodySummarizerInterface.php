<?php

namespace WPFitter\GuzzleHttp;

use WPFitter\Psr\Http\Message\MessageInterface;
/** @internal */
interface BodySummarizerInterface
{
    /**
     * Returns a summarized message body.
     */
    public function summarize(MessageInterface $message) : ?string;
}
