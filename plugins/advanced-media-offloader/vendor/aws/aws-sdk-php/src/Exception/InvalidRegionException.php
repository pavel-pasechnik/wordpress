<?php

namespace WPFitter\Aws\Exception;

use WPFitter\Aws\HasMonitoringEventsTrait;
use WPFitter\Aws\MonitoringEventsInterface;
/** @internal */
class InvalidRegionException extends \RuntimeException implements MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
