<?php

namespace Workbench\App\Jobs;

use Goopil\OtlpMetrics\Contracts\Queue\ShouldTrackJobDuration;
use Goopil\OtlpMetrics\Contracts\Queue\ShouldTrackJobMetrics;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class FailJob implements ShouldQueue, ShouldTrackJobDuration, ShouldTrackJobMetrics
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        throw new RuntimeException('This job failed on purpose for testing OTLP metrics.');
    }
}
