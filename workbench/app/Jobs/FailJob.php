<?php

namespace Workbench\App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Goopil\OtlpMetrics\Contracts\Queue\ShouldTrackJobMetrics;
use Goopil\OtlpMetrics\Contracts\Queue\ShouldTrackJobDuration;
use RuntimeException;

class FailJob implements ShouldQueue, ShouldTrackJobMetrics, ShouldTrackJobDuration
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        throw new RuntimeException('This job failed on purpose for testing OTLP metrics.');
    }
}
