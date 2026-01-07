<?php

namespace Goopil\OtlpMetrics\Tests\Unit;

use Goopil\OtlpMetrics\Configs\ApiFeaturesConfig;
use Goopil\OtlpMetrics\Configs\CronJobFeaturesConfig;
use Goopil\OtlpMetrics\Configs\WorkerFeaturesConfig;
use Goopil\OtlpMetrics\Tests\TestCase;

class FeaturesConfigTest extends TestCase
{
    public function test_worker_features_config_defaults(): void
    {
        $config = new WorkerFeaturesConfig();

        $this->assertTrue($config->trackJobGlobalStateCount);
        $this->assertTrue($config->trackJobGlobalTiming);
    }

    public function test_worker_features_config_from_array(): void
    {
        $config = WorkerFeaturesConfig::fromArray([
            'track_job_global_state_count' => false,
            'track_job_global_timing' => false,
        ]);

        $this->assertFalse($config->trackJobGlobalStateCount);
        $this->assertFalse($config->trackJobGlobalTiming);
    }

    public function test_cronjob_features_config_defaults(): void
    {
        $config = new CronJobFeaturesConfig();

        $this->assertTrue($config->trackScheduledTaskGlobalStateCount);
        $this->assertTrue($config->trackScheduledTaskGlobalTiming);
    }

    public function test_cronjob_features_config_from_array(): void
    {
        $config = CronJobFeaturesConfig::fromArray([
            'track_scheduled_task_global_state_count' => false,
            'track_scheduled_task_global_timing' => false,
        ]);

        $this->assertFalse($config->trackScheduledTaskGlobalStateCount);
        $this->assertFalse($config->trackScheduledTaskGlobalTiming);
    }

    public function test_api_features_config_instantiation(): void
    {
        $config = new ApiFeaturesConfig();
        $this->assertInstanceOf(ApiFeaturesConfig::class, $config);

        $config = ApiFeaturesConfig::fromArray([]);
        $this->assertInstanceOf(ApiFeaturesConfig::class, $config);
    }
}
