<?php

namespace Goopil\OtlpMetrics\Tests\Feature;

use Goopil\OtlpMetrics\Contracts\MetricsServiceInterface;
use Goopil\OtlpMetrics\Http\Middleware\MetricsMiddleware;
use Goopil\OtlpMetrics\Services\MetricsService;
use Goopil\OtlpMetrics\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mockery;
use OpenTelemetry\API\Metrics\CounterInterface;
use OpenTelemetry\API\Metrics\HistogramInterface;

class MiddlewareTest extends TestCase
{
    public function test_middleware_records_metrics(): void
    {
        $counter = Mockery::mock(CounterInterface::class);
        $counter->expects('add')
            ->with(1, Mockery::on(function ($tags) {
                return $tags['method'] === 'GET' && $tags['status'] === 200;
            }));

        $histogram = Mockery::mock(HistogramInterface::class);
        $histogram->expects('record')
            ->with(Mockery::type('float'), Mockery::on(function ($tags) {
                return $tags['method'] === 'GET' && $tags['status'] === 200;
            }));

        $metricsService = Mockery::mock(MetricsService::class);
        $metricsService->allows('counter')
            ->with('http_requests_total', Mockery::any())
            ->andReturns($counter);
        $metricsService->allows('histogram')
            ->with('http_request_duration_ms', Mockery::any(), 'ms')
            ->andReturns($histogram);

        $this->app->instance(MetricsServiceInterface::class, $metricsService);

        // We need to re-bind the tracker to use the mocked metrics service
        $tracker = new \Goopil\OtlpMetrics\Trackers\Http\HttpTracker(
            $metricsService,
            $this->app->make(\Goopil\OtlpMetrics\Configs\ContextConfig::class),
            $this->app->make(\Psr\Log\LoggerInterface::class)
        );
        $this->app->instance(\Goopil\OtlpMetrics\Trackers\Http\HttpTracker::class, $tracker);

        $middleware = $this->app->make(MetricsMiddleware::class);
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('OK', 200);
        });

        $middleware->terminate($request, $response);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_middleware_can_be_disabled(): void
    {
        config(['otlp-metrics.api.enabled' => false]);
        $this->app->forgetInstance(\Goopil\OtlpMetrics\Configs\ContextConfig::class);

        $metricsService = Mockery::mock(MetricsService::class);
        $metricsService->allows('counter')->never();
        $metricsService->allows('histogram')->never();
        $metricsService->allows('export')->never();

        $this->app->instance(MetricsServiceInterface::class, $metricsService);

        $middleware = $this->app->make(MetricsMiddleware::class);
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('OK', 200);
        });

        $middleware->terminate($request, $response);
    }
}
