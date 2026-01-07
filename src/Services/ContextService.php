<?php

namespace Goopil\OtlpMetrics\Services;

use Goopil\OtlpMetrics\Configs\ContextConfig;
use Goopil\OtlpMetrics\Exceptions\ContextException;

class ContextService
{
    protected ?string $context = null;

    public function __construct(
        protected ContextConfig $config,
        protected bool $isRunningInConsole = false
    ) {}

    /**
     * Detect the current execution context
     */
    public function detect(bool $refresh = false): string
    {
        if (! $refresh && $this->context !== null) {
            return $this->context;
        }

        // Check if we are in a cron job (via force_context)
        $forceContext = $this->config->cronjob->forceContext;
        if ($forceContext !== null) {
            return $this->context = $forceContext;
        }

        // Auto-detection
        if ($this->config->cronjob->autoDetectContext) {
            // Detect if we are in a scheduled task
            if ($this->isCronJob()) {
                return $this->context = 'cronjob';
            }

            // Detect if we are in a worker (includes Horizon)
            if ($this->isWorker()) {
                return $this->context = 'worker';
            }
        }

        // By default, we consider we are in the API/Web context
        return $this->context = 'api';
    }

    /**
     * Check if we are in a cron job
     */
    public function isCronJob(): bool
    {
        // Check common environment variables for cron jobs
        if (getenv('CRON') === '1' || getenv('SCHEDULED_TASK') === '1') {
            return true;
        }

        // Check if we are in a schedule:run command
        $command = $_SERVER['argv'][0] ?? '';
        if (str_contains($command, 'schedule:run') || str_contains($command, 'schedule:work')) {
            return true;
        }

        // Check the current command name
        if (isset($_SERVER['argv'][1])) {
            $artisanCommand = $_SERVER['argv'][1] ?? '';
            if (str_contains($artisanCommand, 'schedule')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if we are in a worker
     */
    public function isWorker(): bool
    {
        // Check if we are in horizon
        if ($this->isHorizon()) {
            return true;
        }

        // Check common environment variables for workers
        if (getenv('QUEUE_WORKER') === '1' || getenv('WORKER') === '1') {
            return true;
        }

        // Check if we are in a queue:work command
        $command = $_SERVER['argv'][0] ?? '';
        if (str_contains($command, 'queue:work') || str_contains($command, 'queue:listen')) {
            return true;
        }

        // Check the current command name
        if (isset($_SERVER['argv'][1])) {
            $artisanCommand = $_SERVER['argv'][1] ?? '';
            if (str_contains($artisanCommand, 'queue:work') || str_contains($artisanCommand, 'queue:listen')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if we are in horizon
     */
    public function isHorizon(): bool
    {
        // Check if we are in a horizon command
        $command = $_SERVER['argv'][0] ?? '';
        if (str_contains($command, 'horizon')) {
            return true;
        }

        // Check the current command name
        if (isset($_SERVER['argv'][1])) {
            $artisanCommand = $_SERVER['argv'][1] ?? '';
            if (str_contains($artisanCommand, 'horizon')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if we are in the API/Web context
     */
    public function isApi(): bool
    {
        return ! $this->isRunningInConsole || (! $this->isCronJob() && ! $this->isWorker());
    }

    /**
     * Get the specific configuration object for the current context
     */
    public function getActiveContextConfig(): mixed
    {
        $context = $this->detect();

        return match ($context) {
            'api' => $this->config->api,
            'worker' => $this->config->worker,
            'cronjob' => $this->config->cronjob,
            default => throw new ContextException("Unknown context: {$context}"),
        };
    }

    /**
     * Check if metrics are enabled for the given context
     */
    public function isContextEnabled(string $context): bool
    {
        return match ($context) {
            'api' => $this->config->api->enabled,
            'worker' => $this->config->worker->enabled,
            'cronjob' => $this->config->cronjob->enabled,
            default => false,
        };
    }

    /**
     * Check if metrics are enabled for the current context
     */
    public function isEnabled(): bool
    {
        return $this->isContextEnabled($this->detect());
    }
}
