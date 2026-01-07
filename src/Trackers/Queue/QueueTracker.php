<?php

namespace Goopil\OtlpMetrics\Trackers\Queue;

use Goopil\OtlpMetrics\Configs\ContextConfig;
use Goopil\OtlpMetrics\Contracts\MetricsServiceInterface;
use Goopil\OtlpMetrics\Contracts\Queue\ShouldTrackJobDuration;
use Goopil\OtlpMetrics\Contracts\Queue\ShouldTrackJobMetrics;
use Goopil\OtlpMetrics\Trackers\Queue\Storage\QueueStorage;
use Goopil\OtlpMetrics\Trackers\Queue\Storage\QueueStoredJob;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Psr\Log\LoggerInterface;

class QueueTracker
{
    public function __construct(
        protected MetricsServiceInterface $metricsService,
        protected QueueStorage $storage,
        protected Dispatcher $events,
        protected LoggerInterface $logger,
        protected ContextConfig $config
    ) {}

    /**
     * Register listeners to track queue jobs
     */
    public function register(): void
    {
        $this->events->listen(JobProcessing::class, [$this, 'onJobProcessing']);
        $this->events->listen(JobProcessed::class, [$this, 'onJobProcessed']);
        $this->events->listen(JobFailed::class, [$this, 'onJobFailed']);
    }

    /**
     * Called when a job starts processing
     */
    public function onJobProcessing(JobProcessing $event): void
    {
        $job = $event->job;
        $trackMetrics = $this->shouldTrackMetrics($job);
        $trackDuration = $this->shouldTrackDuration($job);

        if (! $trackMetrics && ! $trackDuration) {
            return;
        }

        if ($trackDuration) {
            $this->storage->put($job->getJobId(), new QueueStoredJob(
                startTime: microtime(true),
                name: $job->getName(),
                queue: $job->getQueue(),
            ));
        }

        if ($trackMetrics) {
            try {
                $this->metricsService
                    ->counter(
                        'queue_jobs_started_total',
                        'Total number of queue jobs started'
                    )
                    ->add(1, [
                        'job' => $job->getName(),
                        'queue' => $job->getQueue(),
                    ]);
            } catch (\Exception $e) {
                $this->logger->warning('Failed to record queue job started metric: '.$e->getMessage());
            }
        }
    }

    /**
     * Called when a job is processed successfully
     */
    public function onJobProcessed(JobProcessed $event): void
    {
        $job = $event->job;
        $jobId = $job->getJobId();
        $trackMetrics = $this->shouldTrackMetrics($job);
        $trackDuration = $this->shouldTrackDuration($job);

        if ($trackDuration && $storedJob = $this->storage->pull($jobId)) {
            try {
                $this->metricsService
                    ->histogram(
                        'queue_jobs_duration_ms',
                        'Duration of queue jobs in milliseconds',
                        'ms'
                    )
                    ->record($storedJob->duration(), [
                        'job' => $storedJob->name,
                        'queue' => $storedJob->queue,
                        'status' => 'success',
                    ]);
            } catch (\Exception $e) {
                $this->logger->warning('Failed to record queue job duration metric: '.$e->getMessage());
            }
        }

        if ($trackMetrics) {
            try {
                $this->metricsService
                    ->counter(
                        'queue_jobs_completed_total',
                        'Total number of queue jobs completed successfully'
                    )
                    ->add(1, [
                        'job' => $job->getName(),
                        'queue' => $job->getQueue(),
                    ]);
            } catch (\Exception $e) {
                $this->logger->warning('Failed to record queue job completed metric: '.$e->getMessage());
            }
        }
    }

    /**
     * Called when a job fails
     */
    public function onJobFailed(JobFailed $event): void
    {
        $job = $event->job;
        $jobId = $job->getJobId();
        $trackMetrics = $this->shouldTrackMetrics($job);
        $trackDuration = $this->shouldTrackDuration($job);

        if ($trackDuration && $storedJob = $this->storage->pull($jobId)) {
            try {
                $this->metricsService
                    ->histogram(
                        'queue_jobs_duration_ms',
                        'Duration of queue jobs in milliseconds',
                        'ms'
                    )
                    ->record($storedJob->duration(), [
                        'job' => $storedJob->name,
                        'queue' => $storedJob->queue,
                        'status' => 'failed',
                    ]);
            } catch (\Exception $e) {
                $this->logger->warning('Failed to record queue job duration metric (failed job): '.$e->getMessage());
            }
        }

        if ($trackMetrics) {
            try {
                $this->metricsService
                    ->counter(
                        'queue_jobs_failed_total',
                        'Total number of queue jobs that failed'
                    )
                    ->add(1, [
                        'job' => $job->getName(),
                        'queue' => $job->getQueue(),
                    ]);
            } catch (\Exception $e) {
                $this->logger->warning('Failed to record queue job failed metric: '.$e->getMessage());
            }
        }
    }

    protected function shouldTrackMetrics($job): bool
    {
        return $this->config->worker->features->trackJobGlobalStateCount ||
            is_a($job->resolveName(), ShouldTrackJobMetrics::class, true);
    }

    protected function shouldTrackDuration($job): bool
    {
        return $this->config->worker->features->trackJobGlobalTiming ||
            is_a($job->resolveName(), ShouldTrackJobDuration::class, true);
    }
}
