<?php

namespace Goopil\OtlpMetrics\Trackers\Queue\Storage;

use Illuminate\Support\Collection;

class QueueStorage extends Collection
{
    /**
     * @param  mixed  $key
     * @param  mixed  $default
     * @return QueueStoredJob|null
     */
    public function pull($key, $default = null)
    {
        $this->purgeExpired();

        $job = parent::pull($key, $default);

        return $job instanceof QueueStoredJob ? $job : null;
    }

    /**
     * Purge items older than 24 hours
     */
    protected function purgeExpired(): void
    {
        $limit = microtime(true) - (24 * 3600);

        $this->items = array_filter(
            $this->items,
            fn ($job) => $job instanceof QueueStoredJob && $job->startTime > $limit
        );
    }
}
