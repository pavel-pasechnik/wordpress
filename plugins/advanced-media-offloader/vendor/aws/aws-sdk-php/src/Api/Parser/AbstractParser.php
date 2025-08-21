<?php

namespace WPFitter\Aws\Api\Parser;

use WPFitter\Aws\Api\Service;
use WPFitter\Aws\Api\StructureShape;
use WPFitter\Aws\CommandInterface;
use WPFitter\Aws\ResultInterface;
use WPFitter\Psr\Http\Message\ResponseInterface;
use WPFitter\Psr\Http\Message\StreamInterface;
/**
 * @internal
 */
abstract class AbstractParser
{
    /** @var \Aws\Api\Service Representation of the service API*/
    protected $api;
    /** @var callable */
    protected $parser;
    /**
     * @param Service $api Service description.
     */
    public function __construct(Service $api)
    {
        $this->api = $api;
    }
    /**
     * @param CommandInterface  $command  Command that was executed.
     * @param ResponseInterface $response Response that was received.
     *
     * @return ResultInterface
     */
    public abstract function __invoke(CommandInterface $command, ResponseInterface $response);
    public abstract function parseMemberFromStream(StreamInterface $stream, StructureShape $member, $response);
}
