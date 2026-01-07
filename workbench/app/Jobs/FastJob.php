<?php

namespace Workbench\App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Goopil\OtlpMetrics\Contracts\Queue\ShouldTrackJobMetrics;
use Goopil\OtlpMetrics\Contracts\Queue\ShouldTrackJobDuration;

class FastJob implements ShouldQueue, ShouldTrackJobMetrics, ShouldTrackJobDuration
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Execute quickly
    }
}
