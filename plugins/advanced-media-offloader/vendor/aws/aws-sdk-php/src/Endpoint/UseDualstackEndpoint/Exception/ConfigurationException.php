<?php

namespace WPFitter\Aws\Endpoint\UseDualstackEndpoint\Exception;

use WPFitter\Aws\HasMonitoringEventsTrait;
use WPFitter\Aws\MonitoringEventsInterface;
/**
 * Represents an error interacting with configuration for useDualstackRegion
 * @internal
 */
class ConfigurationException extends \RuntimeException implements MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
