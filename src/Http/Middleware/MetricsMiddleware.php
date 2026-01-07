<?php

namespace Goopil\OtlpMetrics\Http\Middleware;

use Closure;
use Goopil\OtlpMetrics\Configs\ContextConfig;
use Goopil\OtlpMetrics\Contracts\MetricsServiceInterface;
use Goopil\OtlpMetrics\Trackers\Http\HttpTracker;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class MetricsMiddleware
{
    protected float $start;

    public function __construct(
        protected MetricsServiceInterface $metricsService,
        protected ContextConfig $config,
        protected HttpTracker $tracker,
        protected LoggerInterface $logger
    ) {
        $this->start = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * Terminate the middleware and export metrics.
     */
    public function terminate(Request $request, Response $response): void
    {
        if (! $this->config->api->enabled) {
            return;
        }

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        $this->tracker->track($request, $response, $this->start);
    }
}
