# Laravel OpenTelemetry Metrics

A Laravel package for sending Prometheus metrics via OpenTelemetry with support for gRPC and HTTP, with separate
configuration for API, Workers, and CronJobs.

## Installation

```bash
composer require goopil/otlp-metrics
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=otl-metrics-config
```

### Context-Based Configuration

The configuration is separated into 4 sections: **common**, **API**, **Worker**, and **CronJob**.

#### Common Configuration

```env
OTEL_SERVICE_NAME=laravel-app
OTEL_SERVICE_ENV=local
```

#### API Configuration (Web)

```env
# Enable metrics for API
OTL_METRICS_API_ENABLED=true

OTL_METRICS_API_PUSH_ENDPOINT=http://localhost:4318/v1/metrics
OTL_METRICS_API_PUSH_PROTOCOL=http/protobuf
```

#### Worker Configuration

```env
# Enable metrics for workers
OTL_METRICS_WORKER_ENABLED=true

OTL_METRICS_WORKER_PUSH_ENDPOINT=http://localhost:4318/v1/metrics
OTL_METRICS_WORKER_PUSH_PROTOCOL=http/protobuf

# Automatically track queue jobs
OTL_METRICS_WORKER_TRACK_JOBS=true
```

#### CronJob Configuration

```env
# Enable metrics for cron jobs
OTL_METRICS_CRONJOB_ENABLED=true

OTL_METRICS_CRONJOB_PUSH_ENDPOINT=http://localhost:4318/v1/metrics
OTL_METRICS_CRONJOB_PUSH_PROTOCOL=http/protobuf

# Automatically track scheduled tasks
OTL_METRICS_CRONJOB_TRACK_SCHEDULED_TASKS=true

# Auto-detect context (or force with OTL_METRICS_CRONJOB_FORCE_CONTEXT=cronjob)
OTL_METRICS_CRONJOB_AUTO_DETECT=true
```

#### Features

```env
# Enable/disable features
OTL_METRICS_FEATURE_QUEUE_JOBS=true
OTL_METRICS_FEATURE_SCHEDULED_TASKS=true
OTL_METRICS_FEATURE_HORIZON=true
```

### Supported Protocols

- `http/protobuf` : Push via HTTP with Protobuf (default)
- `grpc` : Push via gRPC

## Usage

### Via Facade

```php
use Goopil\OtlpMetrics\Facades\OtlpMetrics;

// Counter
$counter = OtlpMetrics::counter('requests_total', 'Total number of requests');
$counter->add(1, ['endpoint' => '/api/users']);

// Histogram
$histogram = OtlpMetrics::histogram('request_duration_ms', 'Request duration', 'ms');
$histogram->record(150.5, ['endpoint' => '/api/users']);

// Gauge
$gauge = OtlpMetrics::gauge('active_connections', 'Active connections');
$gauge->record(42, ['type' => 'database']);

// Observable Counter
OtlpMetrics::observableCounter('cpu_usage', function() {
    return sys_getloadavg()[0];
}, 'CPU usage');

// Observable Gauge
OtlpMetrics::observableGauge('memory_usage', function() {
    return memory_get_usage(true);
}, 'Memory usage', 'bytes');
```

### Via Dependency Injection

```php
use Goopil\OtlpMetrics\Services\MetricsService;

class MyController
{
    public function __construct(
        protected MetricsService $metrics
    ) {}

    public function index()
    {
        $counter = $this->metrics->counter('page_views');
        $counter->add(1);
    }
}
```

### HTTP Middleware (API)

The package includes a middleware to automatically record HTTP metrics. When enabled, it also handles the export of
metrics at the end of the request.

```php
// In app/Http/Kernel.php
protected $middleware = [
    // ...
    \Goopil\OtlpMetrics\Http\Middleware\MetricsMiddleware::class,
];
```

The middleware automatically records:

- `http_requests_total` : Total number of HTTP requests
- `http_request_duration_ms` : HTTP request duration in milliseconds

### Queue Jobs Tracking (Workers)

Queue job tracking is automatic if enabled. However, to keep metrics clean and focused, **jobs must implement specific
interfaces** to be tracked:

- Implement `Goopil\OtlpMetrics\Contracts\Queue\ShouldTrackJobMetrics` to track job counts (`started`, `completed`,
  `failed`).
- Implement `Goopil\OtlpMetrics\Contracts\Queue\ShouldTrackJobDuration` to track job duration (histogram).

Example:

```php
use Goopil\OtlpMetrics\Contracts\Queue\ShouldTrackJobMetrics;
use Goopil\OtlpMetrics\Contracts\Queue\ShouldTrackJobDuration;

class MyTrackedJob implements ShouldTrackJobMetrics, ShouldTrackJobDuration
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    // ...
}
```

The following metrics are recorded:

- `queue_jobs_started_total` : Total number of jobs started
- `queue_jobs_completed_total` : Total number of jobs completed successfully
- `queue_jobs_failed_total` : Total number of jobs that failed
- `queue_jobs_duration_ms` : Job duration in milliseconds

### Scheduled Tasks Tracking (CronJobs)

#### Automatic Tracking

Scheduled task tracking is automatic if enabled. The following metrics are recorded:

- `scheduled_tasks_started_total` : Total number of tasks started
- `scheduled_tasks_completed_total` : Total number of tasks completed successfully
- `scheduled_tasks_failed_total` : Total number of tasks that failed
- `scheduled_tasks_duration_ms` : Task duration in milliseconds

#### Scheduler Macros

You can use macros on the `Schedule` to track your tasks:

```php
// In app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // Example 1: Add metrics to a specific command
    $schedule->command('backup:database')
        ->daily()
        ->withMetrics('database_backup', 'Database backup scheduled task');

    // Example 2: Track all tasks in the schedule
    $schedule->trackMetrics('scheduled_task_executions');

    // Example 3: Combine with other methods
    $schedule->command('clean:cache')
        ->hourly()
        ->withMetrics('cache_cleanup', 'Cache cleanup task')
        ->onSuccess(function () {
            // Your logic
        });
}
```

### Context Detection

The library automatically detects the execution context:

```php
use Goopil\OtlpMetrics\Facades\OtlpContext;

$context = OtlpContext::detect(); // 'api', 'worker', or 'cronjob'
// Note: Horizon processes are detected as 'worker' context.

if (OtlpContext::isWorker()) {
    // Worker-specific logic
}

if (OtlpContext::isCronJob()) {
    // Cron job-specific logic
}
```

### Attribute Manager

The package includes an Attribute Manager service for managing attributes for the current scope (request or job):

```php
use Goopil\OtlpMetrics\Facades\OtlpAttributes;
use Goopil\OtlpMetrics\Facades\OtlpMetrics;

// Add attributes (applied to all metrics in the current scope)
OtlpAttributes::addAttribute('environment', 'production');
OtlpAttributes::addAttribute('user_id', '12345');

// Metrics automatically include all scope attributes
$counter = OtlpMetrics::counter('api_requests_total');
$counter->add(1, ['endpoint' => '/api/users']);
// Includes: environment, user_id, endpoint, plus the service context
```

### Laravel Horizon Support

If you're using Laravel Horizon, the package can automatically track Horizon metrics:

```env
OTL_METRICS_FEATURE_HORIZON=true
OTL_METRICS_HORIZON_ENABLED=true
OTL_METRICS_HORIZON_COLLECTION_INTERVAL=15
```

The following Horizon metrics are collected:

- `horizon_queue_pending{queue}` - Number of pending jobs
- `horizon_queue_processing{queue}` - Number of processing jobs
- `horizon_queue_completed{queue}` - Number of completed jobs
- `horizon_queue_failed{queue}` - Number of failed jobs
- `horizon_job_throughput{queue}` - Jobs processed per minute
- `horizon_job_wait_time_ms{queue}` - Job wait time
- `horizon_job_runtime_ms{queue}` - Job runtime
- `horizon_workers_total` - Total number of workers
- `horizon_processes_total` - Total number of processes

## Testing with Docker Compose

The project includes a Docker Compose configuration for local testing:

```bash
docker-compose up -d
```

This starts:

- **OpenTelemetry Collector** : Ports 4317 (gRPC) and 4318 (HTTP)
- **Prometheus** : Port 9090
- **Grafana** : Port 3000 (admin/admin)

### Test Configuration

To test with gRPC:

```env
OTL_METRICS_WORKER_PUSH_ENDPOINT=http://localhost:4317
OTL_METRICS_WORKER_PUSH_PROTOCOL=grpc
```

To test with HTTP:

```env
OTL_METRICS_WORKER_PUSH_ENDPOINT=http://localhost:4318/v1/metrics
OTL_METRICS_WORKER_PUSH_PROTOCOL=http/protobuf
```

## Resilience and Error Handling

The package is designed to be resilient. By default, it suppresses exceptions during initialization and export to avoid
breaking your application if the OTLP collector is unavailable.

### Configuration

```php
'common' => [
    'timeout' => 10,
    'suppress_exceptions' => true, // default: true
],
```

### Typed Exceptions

If you disable exception suppression, you can catch specific exceptions:

- `Goopil\OtlpMetrics\Exceptions\MetricsInitializationException`: Failed to initialize the SDK.
- `Goopil\OtlpMetrics\Exceptions\MetricsExportException`: Failed to export metrics to the collector.
- `Goopil\OtlpMetrics\Exceptions\ConfigurationException`: Invalid configuration.
- `Goopil\OtlpMetrics\Exceptions\ProtocolException`: Unsupported or misconfigured protocol.
- `Goopil\OtlpMetrics\Exceptions\TransportException`: Network or transport-related error.

All library exceptions implement `Goopil\OtlpMetrics\Exceptions\OtlpMetricsExceptionInterface`.

## Performance

The package is optimized for performance with lazy loading and context-aware service registration.

## Development and Testing

### Laravel Workbench

The package uses [Laravel Workbench](https://github.com/orchestra/testbench-workbench) to facilitate development and
manual testing.

```bash
# Start the test server (available at http://127.0.0.1:8000)
make serve

# Rebuild the workbench environment (migrations, etc.)
make build

# Clean the workbench environment
make clear
```

### Tests

```bash
# Run all tests (unit, feature, and workbench)
make test

# Run compatibility tests (different Laravel versions)
make test-compatibility

# Run compatibility tests via Docker
make test-compatibility-docker
```

## Structure

```text
src/
├── Configs/            # Configuration DTOs and Feature flags for each context
├── Contracts/          # Interfaces and contracts
├── Enums/              # Package enumerations (Protocols, etc.)
├── Exceptions/         # Custom exceptions (Initialization, Configuration, etc.)
├── Facades/            # Laravel Facades (OtlpMetrics, OtlpContext, OtlpAttributes)
├── Http/               # HTTP components (MetricsMiddleware)
├── Providers/          # Service Providers (API, Worker, CronJob)
├── Services/           # Core business logic services
│   ├── ContextService.php
│   ├── MetricsService.php
│   └── NullMetricsService.php
├── Support/            # Helpers, Traits, Macros and Metric utilities
│   ├── Metrics/        # Internal Metric components (AttributeService, Registry, etc.)
│   ├── Macros/         # Laravel Macros (Scheduler)
│   └── Traits/         # Internal Traits
├── Trackers/           # Context-aware metric collectors (HTTP, Queue, CronJob, Horizon)
└── OtlpMetricsServiceProvider.php
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

LGPL-3.0-or-later
