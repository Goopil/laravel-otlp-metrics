<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('test:fast', function () {
    $this->info('Running fast task...');
})->purpose('Fast scheduled task');

Artisan::command('test:slow', function () {
    $this->info('Running slow task...');
    sleep(2);
})->purpose('Slow scheduled task');

Artisan::command('test:fail', function () {
    $this->error('Task failed!');
    throw new \RuntimeException('Scheduled task failed');
})->purpose('Fail scheduled task');
