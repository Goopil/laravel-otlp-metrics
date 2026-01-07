<?php

use Illuminate\Support\Facades\Route;
use Workbench\App\Jobs\FailJob;
use Workbench\App\Jobs\FastJob;
use Workbench\App\Jobs\SlowJob;

Route::get('/', function () {
    return 'Laravel OTLP Metrics Workbench is running!';
});

Route::get('/test/fast', function () {
    return 'Fast response';
});

Route::get('/test/slow', function () {
    sleep(1);

    return 'Slow response';
});

Route::get('/test/fail', function () {
    throw new \RuntimeException('API error occurred');
});

Route::get('/test/dispatch-jobs', function () {
    FastJob::dispatch();
    SlowJob::dispatch();
    FailJob::dispatch();

    return 'Jobs dispatched';
});
