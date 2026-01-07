<?php

namespace Goopil\OtlpMetrics\Tests\Feature;

use Goopil\OtlpMetrics\Contracts\MetricsServiceInterface;
use Goopil\OtlpMetrics\Tests\TestCase;
use Goopil\OtlpMetrics\Trackers\Queue\HorizonTracker;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Mockery;
use OpenTelemetry\API\Metrics\GaugeInterface;
use OpenTelemetry\API\Metrics\HistogramInterface;

class HorizonTrackerTest extends TestCase
{
    public function test_horizon_tracker_collects_detailed_worker_metrics(): void
    {
        $metricsService = Mockery::mock(MetricsServiceInterface::class);
        $metricsRepository = Mockery::mock(MetricsRepository::class);
        $jobRepository = Mockery::mock(JobRepository::class);
        $masterRepository = Mockery::mock(MasterSupervisorRepository::class);
        $supervisorRepository = Mockery::mock(SupervisorRepository::class);

        $gauge = Mockery::mock(GaugeInterface::class);
        $histogram = Mockery::mock(HistogramInterface::class);

        $metricsService->allows('gauge')->andReturns($gauge);
        $metricsService->allows('histogram')->andReturns($histogram);

        // Expectation for Master status
        $gauge->expects('record')
            ->with(1, ['master' => 'master-1'])
            ->once();

        // Expectation for Master worker count
        $gauge->expects('record')
            ->with(3, ['master' => 'master-1'])
            ->once();

        // Expectation for Supervisor processes
        $gauge->expects('record')
            ->with(2, [
                'master' => 'master-1',
                'supervisor' => 'supervisor-1',
                'queues' => 'default',
            ])
            ->once();

        // Expectation for Global aggregates
        $gauge->expects('record')->with(3)->once(); // Total workers
        $gauge->expects('record')->with(3)->once(); // Total processes

        $masterRepository->allows('all')->andReturns([
            (object) [
                'name' => 'master-1',
                'status' => 'running',
                'processes' => [1, 2, 3],
                'totalProcessCount' => 3,
            ],
        ]);

        $supervisorRepository->allows('all')->andReturns([
            (object) [
                'name' => 'supervisor-1',
                'master' => 'master-1',
                'processes' => [1, 2],
                'options' => (object) ['queue' => 'default'],
            ],
        ]);

        $metricsRepository->allows('snapshotsForToday')->andReturns([]);
        $metricsRepository->allows('throughput')->andReturns([]);
        $jobRepository->allows('getRecent')->andReturns(collect([]));

        $tracker = new HorizonTracker(
            $metricsService,
            $metricsRepository,
            $jobRepository,
            $masterRepository,
            $this->app->make(\Psr\Log\LoggerInterface::class),
            $this->app->make(\Illuminate\Contracts\Redis\Factory::class),
            $this->app->make(\Illuminate\Contracts\Config\Repository::class),
            $supervisorRepository
        );

        $tracker->collectMetrics();
    }

    public function test_horizon_tracker_continues_collecting_on_partial_failure(): void
    {
        $metricsService = Mockery::mock(MetricsServiceInterface::class);
        $metricsRepository = Mockery::mock(MetricsRepository::class);
        $jobRepository = Mockery::mock(JobRepository::class);
        $masterRepository = Mockery::mock(MasterSupervisorRepository::class);

        $gauge = Mockery::mock(GaugeInterface::class);
        $metricsService->allows('gauge')->andReturns($gauge);

        // Mock error on queue metrics
        $jobRepository->allows('getRecent')->andThrow(new \RuntimeException('Redis error'));

        // But worker metrics should still be collected
        $masterRepository->allows('all')->andReturns([
            (object) [
                'name' => 'master-1',
                'status' => 'running',
                'processes' => [],
                'totalProcessCount' => 0,
            ],
        ]);

        $gauge->expects('record')->atLeast()->once();

        $metricsRepository->allows('snapshotsForToday')->andReturns([]);
        $metricsRepository->allows('throughput')->andReturns([]);

        config(['horizon.defaults' => ['default' => []]]);

        $tracker = new HorizonTracker(
            $metricsService,
            $metricsRepository,
            $jobRepository,
            $masterRepository,
            $this->app->make(\Psr\Log\LoggerInterface::class),
            $this->app->make(\Illuminate\Contracts\Redis\Factory::class),
            $this->app->make(\Illuminate\Contracts\Config\Repository::class)
        );

        $tracker->collectMetrics();

        $this->assertTrue(true); // If it reached here, it didn't crash
    }
}
