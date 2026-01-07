<?php

namespace Goopil\OtlpMetrics\Support\Macros;

use Goopil\OtlpMetrics\Contracts\MetricsServiceInterface;
use Illuminate\Console\Scheduling\Schedule;

class SchedulerMacros
{
    /**
     * Register macros for the Scheduler
     */
    public static function register(MetricsServiceInterface $metricsService): void
    {
        Schedule::macro('withMetrics', function (string $name, ?string $description = null) use ($metricsService) {
            $this->before(fn () => $metricsService
                ->counter(
                    'scheduled_task_executions_total',
                    $description ?? 'Total number of scheduled task executions'
                )
                ->add(1, ['task' => $name])
            );

            return $this;
        });

        Schedule::macro('trackMetrics', function (string $counterName = 'scheduled_task_executions') use ($metricsService) {
            foreach ($this->events() as $event) {
                $event->before(fn () => $metricsService
                    ->counter(
                        $counterName.'_total',
                        'Total number of scheduled task executions'
                    )
                    ->add(1, ['task' => SchedulerMacros::getTaskName($event)])
                );

                $event->onSuccess(fn () => $metricsService
                    ->counter(
                        $counterName.'_success_total',
                        'Total number of successful scheduled task executions'
                    )
                    ->add(1, ['task' => SchedulerMacros::getTaskName($event)])
                );

                $event->onFailure(fn () => $metricsService
                    ->counter(
                        $counterName.'_failed_total',
                        'Total number of failed scheduled task executions'
                    )->add(1, ['task' => SchedulerMacros::getTaskName($event)])
                );
            }

            return $this;
        });
    }

    /**
     * Get the task name
     */
    public static function getTaskName($event): string
    {
        if (method_exists($event, 'getSummaryForDisplay')) {
            return $event->getSummaryForDisplay();
        }

        return 'unknown';
    }
}
