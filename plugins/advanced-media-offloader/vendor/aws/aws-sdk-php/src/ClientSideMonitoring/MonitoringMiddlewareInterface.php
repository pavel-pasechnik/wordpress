<?php

namespace WPFitter\Aws\ClientSideMonitoring;

use WPFitter\Aws\CommandInterface;
use WPFitter\Aws\Exception\AwsException;
use WPFitter\Aws\ResultInterface;
use WPFitter\GuzzleHttp\Psr7\Request;
use WPFitter\Psr\Http\Message\RequestInterface;
/**
 * @internal
 */
interface MonitoringMiddlewareInterface
{
    /**
     * Data for event properties to be sent to the monitoring agent.
     *
     * @param RequestInterface $request
     * @return array
     */
    public static function getRequestData(RequestInterface $request);
    /**
     * Data for event properties to be sent to the monitoring agent.
     *
     * @param ResultInterface|AwsException|\Exception $klass
     * @return array
     */
    public static function getResponseData($klass);
    public function __invoke(CommandInterface $cmd, RequestInterface $request);
}
