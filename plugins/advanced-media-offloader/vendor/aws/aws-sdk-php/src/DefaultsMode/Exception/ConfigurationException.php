<?php

namespace WPFitter\Aws\DefaultsMode\Exception;

use WPFitter\Aws\HasMonitoringEventsTrait;
use WPFitter\Aws\MonitoringEventsInterface;
/**
 * Represents an error interacting with configuration mode
 * @internal
 */
class ConfigurationException extends \RuntimeException implements MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
