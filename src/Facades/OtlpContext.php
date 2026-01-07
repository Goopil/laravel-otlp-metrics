<?php

namespace Goopil\OtlpMetrics\Facades;

use Goopil\OtlpMetrics\Services\ContextService as ContextDetectorService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string detect()
 * @method static bool isCronJob()
 * @method static bool isWorker()
 * @method static bool isApi()
 * @method static mixed getActiveContextConfig()
 * @method static bool isContextEnabled(string $context)
 * @method static bool isEnabled()
 *
 * @see \Goopil\OtlpMetrics\Services\ContextService
 */
class OtlpContext extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ContextDetectorService::class;
    }
}
