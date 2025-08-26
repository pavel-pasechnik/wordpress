<?php

namespace WPFitter\Aws\Exception;

use WPFitter\Aws\HasMonitoringEventsTrait;
use WPFitter\Aws\MonitoringEventsInterface;
/** @internal */
class CredentialsException extends \RuntimeException implements MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
