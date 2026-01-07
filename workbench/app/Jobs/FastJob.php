<?php

namespace Workbench\App\Jobs;

use Goopil\OtlpMetrics\Contracts\Queue\ShouldTrackJobDuration;
use Goopil\OtlpMetrics\Contracts\Queue\ShouldTrackJobMetrics;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FastJob implements ShouldQueue, ShouldTrackJobDuration, ShouldTrackJobMetrics
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Execute quickly
    }
}
