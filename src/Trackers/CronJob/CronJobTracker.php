<?php

namespace Goopil\OtlpMetrics\Trackers\CronJob;

use Goopil\OtlpMetrics\Configs\ContextConfig;
use Goopil\OtlpMetrics\Contracts\MetricsServiceInterface;
use Goopil\OtlpMetrics\Trackers\CronJob\Storage\CronjobStorage;
use Goopil\OtlpMetrics\Trackers\CronJob\Storage\CronJobStoredTask;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Events\Dispatcher;
use Psr\Log\LoggerInterface;

class CronJobTracker
{
    public function __construct(
        protected MetricsServiceInterface $metricsService,
        protected CronjobStorage $storage,
        protected Dispatcher $dispatcher,
        protected LoggerInterface $logger,
        protected ContextConfig $config
    ) {}

    /**
     * Register listeners to track scheduled tasks
     */
    public function register(): void
    {
        $this->dispatcher->listen(ScheduledTaskStarting::class, [$this, 'onTaskStarting']);
        $this->dispatcher->listen(ScheduledTaskFinished::class, [$this, 'onTaskFinished']);
        $this->dispatcher->listen(ScheduledTaskFailed::class, [$this, 'onTaskFailed']);
    }

    /**
     * Called when a scheduled task starts
     */
    public function onTaskStarting(ScheduledTaskStarting $event): void
    {
        $taskId = $this->getTaskId($event);
        $command = $this->getTaskCommand($event);

        if ($this->shouldTrackDuration()) {
            $this->storage->put(
                $taskId,
                new CronJobStoredTask($command)
            );
        }

        if ($this->shouldTrackMetrics()) {
            try {
                $this->metricsService
                    ->counter(
                        'scheduled_tasks_started_total',
                        'Total number of scheduled tasks started'
                    )
                    ->add(1, ['command' => $command]);
            } catch (\Exception $e) {
                $this->logger->warning('Failed to record scheduled task started metric: '.$e->getMessage());
            }
        }
    }

    /**
     * Called when a scheduled task finishes successfully
     */
    public function onTaskFinished(ScheduledTaskFinished $event): void
    {
        $taskId = $this->getTaskId($event);
        $command = $this->getTaskCommand($event);

        if ($task = $this->storage->pull($taskId)) {
            $duration = $task->duration();

            try {
                $this->metricsService
                    ->histogram(
                        'scheduled_tasks_duration_ms',
                        'Duration of scheduled tasks in milliseconds',
                        'ms'
                    )
                    ->record($duration, [
                        'command' => $command,
                        'status' => 'success',
                    ]);
            } catch (\Exception $e) {
                $this->logger->warning('Failed to record scheduled task duration metric: '.$e->getMessage());
            }
        }

        if ($this->shouldTrackMetrics()) {
            try {
                $this->metricsService
                    ->counter(
                        'scheduled_tasks_completed_total',
                        'Total number of scheduled tasks completed successfully'
                    )
                    ->add(1, ['command' => $command]);
            } catch (\Exception $e) {
                $this->logger->warning('Failed to record scheduled task completed metric: '.$e->getMessage());
            }
        }
    }

    /**
     * Called when a scheduled task fails
     */
    public function onTaskFailed(ScheduledTaskFailed $event): void
    {
        $taskId = $this->getTaskId($event);
        $command = $this->getTaskCommand($event);

        if ($task = $this->storage->pull($taskId)) {
            $duration = $task->duration();

            try {
                $this->metricsService
                    ->histogram(
                        'scheduled_tasks_duration_ms',
                        'Duration of scheduled tasks in milliseconds',
                        'ms'
                    )
                    ->record($duration, [
                        'command' => $command,
                        'status' => 'failed',
                    ]);
            } catch (\Exception $e) {
                $this->logger->warning('Failed to record scheduled task duration metric (failed task): '.$e->getMessage());
            }
        }

        if ($this->shouldTrackMetrics()) {
            try {
                $this->metricsService
                    ->counter(
                        'scheduled_tasks_failed_total',
                        'Total number of scheduled tasks that failed'
                    )
                    ->add(1, ['command' => $command]);
            } catch (\Exception $e) {
                $this->logger->warning('Failed to record scheduled task failed metric: '.$e->getMessage());
            }
        }
    }

    protected function shouldTrackMetrics(): bool
    {
        return $this->config->cronjob->features->trackScheduledTaskGlobalStateCount;
    }

    protected function shouldTrackDuration(): bool
    {
        return $this->config->cronjob->features->trackScheduledTaskGlobalTiming;
    }

    protected function getTaskId($event): string
    {
        return spl_object_hash($event->task);
    }

    protected function getTaskCommand($event): string
    {
        if (method_exists($event->task, 'getSummaryForDisplay')) {
            return $event->task->getSummaryForDisplay();
        }

        return 'unknown';
    }
}
