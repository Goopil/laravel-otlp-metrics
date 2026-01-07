<?php

namespace Goopil\OtlpMetrics\Facades;

use Goopil\OtlpMetrics\Support\Metrics\AttributeService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static self addAttribute(string $key, string $value)
 * @method static self addAttributes(array $attributes)
 * @method static self removeAttribute(string $key)
 * @method static self clear()
 * @method static array getAttributes()
 * @method static array mergeAttributes(array $providedAttributes = [])
 * @method static array getAllAttributes()
 *
 * @see \Goopil\OtlpMetrics\Support\Metrics\AttributeService
 */
class OtlpAttributes extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AttributeService::class;
    }
}
