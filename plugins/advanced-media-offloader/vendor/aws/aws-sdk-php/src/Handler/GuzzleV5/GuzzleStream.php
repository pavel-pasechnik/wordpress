<?php

namespace WPFitter\Aws\Handler\GuzzleV5;

use WPFitter\GuzzleHttp\Stream\StreamDecoratorTrait;
use WPFitter\GuzzleHttp\Stream\StreamInterface as GuzzleStreamInterface;
use WPFitter\Psr\Http\Message\StreamInterface as Psr7StreamInterface;
/**
 * Adapts a PSR-7 Stream to a Guzzle 5 Stream.
 *
 * @codeCoverageIgnore
 * @internal
 */
class GuzzleStream implements GuzzleStreamInterface
{
    use StreamDecoratorTrait;
    /** @var Psr7StreamInterface */
    private $stream;
    public function __construct(Psr7StreamInterface $stream)
    {
        $this->stream = $stream;
    }
}
