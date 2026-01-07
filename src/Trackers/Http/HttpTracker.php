<?php

namespace Goopil\OtlpMetrics\Trackers\Http;

use Goopil\OtlpMetrics\Configs\ContextConfig;
use Goopil\OtlpMetrics\Contracts\MetricsServiceInterface;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class HttpTracker
{
    public function __construct(
        protected MetricsServiceInterface $metricsService,
        protected ContextConfig $config,
        protected LoggerInterface $logger
    ) {}

    /**
     * Track an HTTP request
     */
    public function track(Request $request, Response $response, float $startTime): void
    {
        try {
            $duration = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
            $attributes = $this->extractAttributes($request, $response);

            // Record request duration
            $this->metricsService
                ->histogram(
                    'http_request_duration_ms',
                    'HTTP request duration in milliseconds',
                    'ms'
                )
                ->record($duration, $attributes);

            // Record request count
            $this->metricsService
                ->counter(
                    'http_requests_total',
                    'Total number of HTTP requests'
                )
                ->add(1, [
                    'method' => $attributes['method'],
                    'status' => $attributes['status'],
                ]);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to record HTTP metrics: '.$e->getMessage());
        }
    }

    /**
     * Extract attributes from request and response
     */
    protected function extractAttributes(Request $request, Response $response): array
    {
        $route = $request->route();
        $routeName = $route ? ($route->uri() ?: 'unknown') : 'unknown';

        return [
            'method' => $request->method(),
            'route' => $routeName,
            'status' => $response->getStatusCode(),
        ];
    }
}
