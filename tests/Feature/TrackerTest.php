<?php

namespace Goopil\OtlpMetrics\Tests\Feature;

use Goopil\OtlpMetrics\Contracts\Queue\ShouldTrackJobDuration;
use Goopil\OtlpMetrics\Contracts\Queue\ShouldTrackJobMetrics;
use Goopil\OtlpMetrics\Services\MetricsService;
use Goopil\OtlpMetrics\Tests\TestCase;
use Goopil\OtlpMetrics\Trackers\CronJob\CronJobTracker;
use Goopil\OtlpMetrics\Trackers\CronJob\Storage\CronjobStorage;
use Goopil\OtlpMetrics\Trackers\Queue\QueueTracker;
use Goopil\OtlpMetrics\Trackers\Queue\Storage\QueueStorage;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Events\Dispatcher;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Mockery;
use OpenTelemetry\API\Metrics\CounterInterface;
use OpenTelemetry\API\Metrics\HistogramInterface;

class TrackerTest extends TestCase
{
    public function test_queue_job_tracker_records_metrics_when_interfaces_present(): void
    {
        $counter = Mockery::mock(CounterInterface::class);
        $counter->expects('add')->twice();

        $histogram = Mockery::mock(HistogramInterface::class);
        $histogram->expects('record')->once();

        $metricsService = Mockery::mock(MetricsService::class);
        $metricsService->allows('counter')->andReturns($counter);
        $metricsService->allows('histogram')->andReturns($histogram);

        $storage = new QueueStorage();
        $tracker = new QueueTracker(
            $metricsService,
            $storage,
            $this->app->make(\Illuminate\Contracts\Events\Dispatcher::class),
            $this->app->make(\Psr\Log\LoggerInterface::class),
            $this->app->make(\Goopil\OtlpMetrics\Configs\ContextConfig::class)
        );

        $job = Mockery::mock(\Illuminate\Contracts\Queue\Job::class);
        $job->allows('getJobId')->andReturns('123');
        $job->allows('getName')->andReturns(TrackedJob::class);
        $job->allows('getQueue')->andReturns('default');
        $job->allows('resolveName')->andReturns(TrackedJob::class);

        $tracker->onJobProcessing(new JobProcessing('connection', $job));
        $tracker->onJobProcessed(new JobProcessed('connection', $job));
    }

    public function test_queue_job_tracker_does_not_record_when_interfaces_absent(): void
    {
        $this->app['config']->set('otlp-metrics.worker.features', [
            'track_job_global_state_count' => false,
            'track_job_global_timing' => false,
        ]);
        $this->app->forgetInstance(\Goopil\OtlpMetrics\Configs\ContextConfig::class);

        $metricsService = Mockery::mock(MetricsService::class);
        $metricsService->expects('counter')->never();
        $metricsService->expects('histogram')->never();

        $storage = new QueueStorage();
        $tracker = new QueueTracker(
            $metricsService,
            $storage,
            $this->app->make(\Illuminate\Contracts\Events\Dispatcher::class),
            $this->app->make(\Psr\Log\LoggerInterface::class),
            $this->app->make(\Goopil\OtlpMetrics\Configs\ContextConfig::class)
        );

        $job = Mockery::mock(\Illuminate\Contracts\Queue\Job::class);
        $job->allows('getJobId')->andReturns('124');
        $job->allows('getName')->andReturns(UntrackedJob::class);
        $job->allows('getQueue')->andReturns('default');
        $job->allows('resolveName')->andReturns(UntrackedJob::class);

        $tracker->onJobProcessing(new JobProcessing('connection', $job));
        $tracker->onJobProcessed(new JobProcessed('connection', $job));
    }

    public function test_queue_job_tracker_records_only_metrics(): void
    {
        $this->app['config']->set('otlp-metrics.worker.features', [
            'track_job_global_state_count' => false,
            'track_job_global_timing' => false,
        ]);
        $this->app->forgetInstance(\Goopil\OtlpMetrics\Configs\ContextConfig::class);

        $counter = Mockery::mock(CounterInterface::class);
        $counter->expects('add')->twice();

        $metricsService = Mockery::mock(MetricsService::class);
        $metricsService->allows('counter')->andReturns($counter);
        $metricsService->expects('histogram')->never();

        $storage = new QueueStorage();
        $tracker = new QueueTracker(
            $metricsService,
            $storage,
            $this->app->make(\Illuminate\Contracts\Events\Dispatcher::class),
            $this->app->make(\Psr\Log\LoggerInterface::class),
            $this->app->make(\Goopil\OtlpMetrics\Configs\ContextConfig::class)
        );

        $job = Mockery::mock(\Illuminate\Contracts\Queue\Job::class);
        $job->allows('getJobId')->andReturns('125');
        $job->allows('getName')->andReturns(OnlyMetricsJob::class);
        $job->allows('getQueue')->andReturns('default');
        $job->allows('resolveName')->andReturns(OnlyMetricsJob::class);

        $tracker->onJobProcessing(new JobProcessing('connection', $job));
        $tracker->onJobProcessed(new JobProcessed('connection', $job));
    }

    public function test_queue_job_tracker_records_only_duration(): void
    {
        $this->app['config']->set('otlp-metrics.worker.features', [
            'track_job_global_state_count' => false,
            'track_job_global_timing' => false,
        ]);
        $this->app->forgetInstance(\Goopil\OtlpMetrics\Configs\ContextConfig::class);

        $histogram = Mockery::mock(HistogramInterface::class);
        $histogram->expects('record')->once();

        $metricsService = Mockery::mock(MetricsService::class);
        $metricsService->expects('counter')->never();
        $metricsService->allows('histogram')->andReturns($histogram);

        $storage = new QueueStorage();
        $tracker = new QueueTracker(
            $metricsService,
            $storage,
            $this->app->make(\Illuminate\Contracts\Events\Dispatcher::class),
            $this->app->make(\Psr\Log\LoggerInterface::class),
            $this->app->make(\Goopil\OtlpMetrics\Configs\ContextConfig::class)
        );

        $job = Mockery::mock(\Illuminate\Contracts\Queue\Job::class);
        $job->allows('getJobId')->andReturns('126');
        $job->allows('getName')->andReturns(OnlyDurationJob::class);
        $job->allows('getQueue')->andReturns('default');
        $job->allows('resolveName')->andReturns(OnlyDurationJob::class);

        $tracker->onJobProcessing(new JobProcessing('connection', $job));
        $tracker->onJobProcessed(new JobProcessed('connection', $job));
    }

    public function test_cron_job_tracker_records_metrics(): void
    {
        $counter = Mockery::mock(CounterInterface::class);
        $counter->expects('add')->twice();

        $histogram = Mockery::mock(HistogramInterface::class);
        $histogram->expects('record');

        $metricsService = Mockery::mock(MetricsService::class);
        $metricsService->allows('counter')->andReturns($counter);
        $metricsService->allows('histogram')->andReturns($histogram);

        $storage = new CronjobStorage();
        $dispatcher = Mockery::mock(Dispatcher::class);
        $tracker = new CronJobTracker(
            $metricsService,
            $storage,
            $dispatcher,
            $this->app->make(\Psr\Log\LoggerInterface::class),
            $this->app->make(\Goopil\OtlpMetrics\Configs\ContextConfig::class)
        );

        $task = Mockery::mock(\Illuminate\Console\Scheduling\Event::class);
        $task->allows('getSummaryForDisplay')->andReturns('test:command');

        $tracker->onTaskStarting(new ScheduledTaskStarting($task));
        $tracker->onTaskFinished(new ScheduledTaskFinished($task, 0.1));
    }

    public function test_cron_job_tracker_records_globally_when_enabled(): void
    {
        $this->app['config']->set('otlp-metrics.cronjob.features', [
            'track_scheduled_task_global_state_count' => true,
            'track_scheduled_task_global_timing' => true,
        ]);
        $this->app->forgetInstance(\Goopil\OtlpMetrics\Configs\ContextConfig::class);

        $counter = Mockery::mock(CounterInterface::class);
        $counter->expects('add')->twice();

        $histogram = Mockery::mock(HistogramInterface::class);
        $histogram->expects('record')->once();

        $metricsService = Mockery::mock(MetricsService::class);
        $metricsService->allows('counter')->andReturns($counter);
        $metricsService->allows('histogram')->andReturns($histogram);

        $storage = new CronjobStorage();
        $dispatcher = Mockery::mock(Dispatcher::class);
        $tracker = new CronJobTracker(
            $metricsService,
            $storage,
            $dispatcher,
            $this->app->make(\Psr\Log\LoggerInterface::class),
            $this->app->make(\Goopil\OtlpMetrics\Configs\ContextConfig::class)
        );

        $task = Mockery::mock(\Illuminate\Console\Scheduling\Event::class);
        $task->allows('getSummaryForDisplay')->andReturns('test:command');

        $tracker->onTaskStarting(new ScheduledTaskStarting($task));
        $tracker->onTaskFinished(new ScheduledTaskFinished($task, 0.1));
    }

    public function test_cron_job_tracker_respects_disabled_flags(): void
    {
        $this->app['config']->set('otlp-metrics.cronjob.features', [
            'track_scheduled_task_global_state_count' => false,
            'track_scheduled_task_global_timing' => false,
        ]);
        $this->app->forgetInstance(\Goopil\OtlpMetrics\Configs\ContextConfig::class);

        $metricsService = Mockery::mock(MetricsService::class);
        $metricsService->expects('counter')->never();
        $metricsService->expects('histogram')->never();

        $storage = new CronjobStorage();
        $dispatcher = Mockery::mock(Dispatcher::class);
        $tracker = new CronJobTracker(
            $metricsService,
            $storage,
            $dispatcher,
            $this->app->make(\Psr\Log\LoggerInterface::class),
            $this->app->make(\Goopil\OtlpMetrics\Configs\ContextConfig::class)
        );

        $task = Mockery::mock(\Illuminate\Console\Scheduling\Event::class);
        $task->allows('getSummaryForDisplay')->andReturns('test:command');

        $tracker->onTaskStarting(new ScheduledTaskStarting($task));
        $tracker->onTaskFinished(new ScheduledTaskFinished($task, 0.1));
    }

    public function test_queue_job_tracker_records_globally_when_enabled(): void
    {
        $this->app['config']->set('otlp-metrics.worker.features', [
            'track_job_global_state_count' => true,
            'track_job_global_timing' => true,
        ]);
        $this->app->forgetInstance(\Goopil\OtlpMetrics\Configs\ContextConfig::class);

        $counter = Mockery::mock(CounterInterface::class);
        $counter->expects('add')->twice();

        $histogram = Mockery::mock(HistogramInterface::class);
        $histogram->expects('record')->once();

        $metricsService = Mockery::mock(MetricsService::class);
        $metricsService->allows('counter')->andReturns($counter);
        $metricsService->allows('histogram')->andReturns($histogram);

        $storage = new QueueStorage();
        $tracker = new QueueTracker(
            $metricsService,
            $storage,
            $this->app->make(\Illuminate\Contracts\Events\Dispatcher::class),
            $this->app->make(\Psr\Log\LoggerInterface::class),
            $this->app->make(\Goopil\OtlpMetrics\Configs\ContextConfig::class)
        );

        // Job without any interface
        $job = Mockery::mock(\Illuminate\Contracts\Queue\Job::class);
        $job->allows('getJobId')->andReturns('127');
        $job->allows('getName')->andReturns(UntrackedJob::class);
        $job->allows('getQueue')->andReturns('default');
        $job->allows('resolveName')->andReturns(UntrackedJob::class);

        $tracker->onJobProcessing(new JobProcessing('connection', $job));
        $tracker->onJobProcessed(new JobProcessed('connection', $job));
    }
}

class TrackedJob implements ShouldTrackJobDuration, ShouldTrackJobMetrics {}
class OnlyMetricsJob implements ShouldTrackJobMetrics {}
class OnlyDurationJob implements ShouldTrackJobDuration {}
class UntrackedJob {}
