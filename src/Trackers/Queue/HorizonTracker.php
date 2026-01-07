<?php

namespace Goopil\OtlpMetrics\Trackers\Queue;

use Goopil\OtlpMetrics\Contracts\MetricsServiceInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\MetricsRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Horizon;
use Psr\Log\LoggerInterface;

class HorizonTracker
{
    public function __construct(
        protected MetricsServiceInterface $metricsService,
        protected MetricsRepository $metricsRepository,
        protected JobRepository $jobRepository,
        protected MasterSupervisorRepository $masterSupervisorRepository,
        protected LoggerInterface $logger,
        protected RedisFactory $redis,
        protected ConfigRepository $configRepository,
        protected ?SupervisorRepository $supervisorRepository = null,
    ) {}

    /**
     * Register the observable gauge for Horizon metrics
     */
    public function register(): void
    {
        try {
            $this->metricsService->observableGauge('horizon_metrics_collector', function ($observer) {
                $this->collectMetrics();
                if (method_exists($observer, 'observe')) {
                    $observer->observe(1);
                }
            }, 'Horizon metrics collection trigger');
        } catch (\Exception $e) {
            $this->logger->debug('Horizon metrics registration skipped: '.$e->getMessage());
        }
    }

    /**
     * Check if Horizon and its required services are available
     */
    public static function isAvailable(bool $isMetricsRepositoryBound = true): bool
    {
        return class_exists(Horizon::class) && $isMetricsRepositoryBound;
    }

    /**
     * Check if Horizon is available (internal check)
     *
     * @deprecated Use static isAvailable instead
     */
    public function isHorizonAvailable(): bool
    {
        return static::isAvailable();
    }

    /**
     * Collect and record Horizon metrics.
     * Note: This is typically called periodically by the WorkerServiceProvider
     * based on the configured collection_interval.
     */
    public function collectMetrics(): void
    {
        $collectors = [
            'recordQueueMetrics',
            'recordJobMetrics',
            'recordWorkerMetrics',
            'recordWaitTimeMetrics',
            'recordThroughputMetrics',
        ];

        foreach ($collectors as $collector) {
            try {
                $this->{$collector}();
            } catch (\Throwable $e) {
                $this->logger->warning("Failed to collect Horizon metrics ($collector): ".$e->getMessage());
            }
        }
    }

    /**
     * Record queue metrics (pending, processing, completed, failed)
     */
    protected function recordQueueMetrics(): void
    {
        // Get all queues from Horizon config
        $queues = array_keys($this->configRepository->get('horizon.defaults', ['default' => []]));

        if (empty($queues)) {
            $queues = ['default'];
        }

        foreach ($queues as $queueName) {
            try {
                $recent = $this->jobRepository->getRecent($queueName);

                // Pending jobs
                $pending = $recent->where('status', 'pending')->count();
                $gauge = $this->metricsService->gauge('horizon_queue_pending', 'Number of pending jobs in Horizon queue');
                $gauge->record($pending, ['queue' => $queueName]);

                // Processing jobs
                $processing = $recent->where('status', 'processing')->count();
                $gauge = $this->metricsService->gauge('horizon_queue_processing', 'Number of processing jobs in Horizon queue');
                $gauge->record($processing, ['queue' => $queueName]);

                // Completed jobs
                $completed = $recent->where('status', 'completed')->count();
                $gauge = $this->metricsService->gauge('horizon_queue_completed', 'Number of completed jobs in Horizon queue');
                $gauge->record($completed, ['queue' => $queueName]);

                // Failed jobs
                $failed = $recent->where('status', 'failed')->count();
                $gauge = $this->metricsService->gauge('horizon_queue_failed', 'Number of failed jobs in Horizon queue');
                $gauge->record($failed, ['queue' => $queueName]);
            } catch (\Exception $e) {
                // Skip this queue if there's an error
                continue;
            }
        }
    }

    /**
     * Record job metrics (throughput, wait time, runtime)
     */
    protected function recordJobMetrics(): void
    {
        if ($this->metricsRepository === null) {
            return;
        }

        $snapshots = $this->metricsRepository->snapshotsForToday();

        foreach ($snapshots as $snapshot) {
            $queue = $snapshot->queue ?? 'default';

            // Throughput (jobs per minute)
            if (isset($snapshot->throughput)) {
                $gauge = $this->metricsService->gauge('horizon_job_throughput', 'Jobs processed per minute');
                $gauge->record($snapshot->throughput, ['queue' => $queue]);
            }

            // Wait time (milliseconds)
            if (isset($snapshot->wait)) {
                $histogram = $this->metricsService->histogram('horizon_job_wait_time_ms', 'Job wait time in milliseconds', 'ms');
                $histogram->record($snapshot->wait, ['queue' => $queue]);
            }

            // Runtime (milliseconds)
            if (isset($snapshot->runtime)) {
                $histogram = $this->metricsService->histogram('horizon_job_runtime_ms', 'Job runtime in milliseconds', 'ms');
                $histogram->record($snapshot->runtime, ['queue' => $queue]);
            }
        }
    }

    /**
     * Record worker metrics (active workers, processes, statuses)
     */
    protected function recordWorkerMetrics(): void
    {
        if ($this->masterSupervisorRepository === null) {
            return;
        }

        $masters = $this->masterSupervisorRepository->all();

        $this->recordMasterMetrics($masters);
        $this->recordSupervisorMetrics();
        $this->recordGlobalWorkerAggregates($masters);
    }

    /**
     * Record metrics for each Master Supervisor
     */
    protected function recordMasterMetrics(array $masters): void
    {
        foreach ($masters as $master) {
            $tags = ['master' => $master->name ?? 'default'];

            // Status: 1=running, 0=paused
            $status = ($master->status ?? 'running') === 'paused' ? 0 : 1;
            $this->metricsService->gauge('horizon_master_status', 'Status of the Horizon master supervisor')
                ->record($status, $tags);

            // Worker processes count for this master
            $this->metricsService->gauge('horizon_master_workers', 'Number of worker processes for this master')
                ->record(count($master->processes ?? []), $tags);
        }
    }

    /**
     * Record metrics for each individual Supervisor pool
     */
    protected function recordSupervisorMetrics(): void
    {
        if ($this->supervisorRepository === null) {
            return;
        }

        foreach ($this->supervisorRepository->all() as $supervisor) {
            $this->metricsService->gauge('horizon_supervisor_processes', 'Number of processes per supervisor pool')
                ->record(count($supervisor->processes ?? []), [
                    'master' => $supervisor->master,
                    'supervisor' => $supervisor->name,
                    'queues' => $supervisor->options->queue ?? 'unknown',
                ]);
        }
    }

    /**
     * Record global worker aggregates for backward compatibility
     */
    protected function recordGlobalWorkerAggregates(array $masters): void
    {
        $totalWorkers = 0;
        $totalProcesses = 0;

        foreach ($masters as $master) {
            $totalWorkers += count($master->processes ?? []);
            $totalProcesses += $master->totalProcessCount ?? 0;
        }

        $this->metricsService->gauge('horizon_workers_total', 'Total number of Horizon workers')
            ->record($totalWorkers);

        $this->metricsService->gauge('horizon_processes_total', 'Total number of Horizon processes')
            ->record($totalProcesses);
    }

    /**
     * Record wait time metrics from Redis
     */
    protected function recordWaitTimeMetrics(): void
    {
        try {
            $redis = $this->redis->connection($this->configRepository->get('horizon.use'));
            $queues = $this->configRepository->get('horizon.defaults', []);

            foreach (array_keys($queues) as $queueName) {
                $waitTime = $redis->zrange("horizon:wait:{$queueName}", 0, -1, 'WITHSCORES');

                if (! empty($waitTime)) {
                    $avgWaitTime = array_sum(array_values($waitTime)) / count($waitTime);

                    $this->metricsService
                        ->histogram('horizon_queue_wait_time_ms', 'Average wait time for queue in milliseconds', 'ms')
                        ->record($avgWaitTime, ['queue' => $queueName]);
                }
            }
        } catch (\Throwable $e) {
            // Horizon Redis connection might not be available
        }
    }

    /**
     * Record throughput metrics
     */
    protected function recordThroughputMetrics(): void
    {
        try {
            foreach ($this->metricsRepository->throughput() as $queue => $jobsPerMinute) {
                try {
                    $this->metricsService
                        ->gauge('horizon_throughput_jobs_per_minute', 'Jobs processed per minute')
                        ->record($jobsPerMinute, ['queue' => $queue]);
                } catch (\Throwable $e) {
                    continue;
                }
            }
        } catch (\Throwable $e) {
            // Metrics might not be available
        }
    }
}
