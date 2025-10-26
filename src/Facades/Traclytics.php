<?php

namespace Traclytics\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array track(array $payload)
 * @method static array trackEvent(array $payload)
 *
 * @see \Traclytics\TraclyticsClient
 */
class Traclytics extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'traclytics';
    }
}

