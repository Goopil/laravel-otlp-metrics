<?php

namespace Goopil\OtlpMetrics\Trackers\CronJob\Storage;

use Illuminate\Support\Collection;

class CronjobStorage extends Collection
{
    /**
     * @param  mixed  $key
     * @param  mixed  $default
     */
    public function pull($key, $default = null): ?CronJobStoredTask
    {
        $this->purgeExpired();

        $job = parent::pull($key, $default);

        return $job instanceof CronJobStoredTask ? $job : null;
    }

    /**
     * Purge items older than 24 hours
     */
    protected function purgeExpired(): void
    {
        $limit = microtime(true) - (24 * 3600);

        $this->items = array_filter(
            $this->items,
            fn ($task) => $task instanceof CronJobStoredTask && $task->startTime > $limit
        );
    }
}
