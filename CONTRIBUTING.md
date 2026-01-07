# Contribution Guide

Thank you for considering contributing to Laravel OpenTelemetry Metrics!

## Pull Requests

1. Fork the repository and create your branch from `main`.
2. If you've added code that should be tested, add tests.
3. If you've changed APIs, update the documentation.
4. Ensure the test suite passes (`make test`).
5. Make sure your code lints (`make lint`).

## Implementation Notes

### Context Detection

Automatic context detection is based on:
- Environment variables (`CRON`, `QUEUE_WORKER`, etc.)
- Command line arguments
- The `OTL_METRICS_CRONJOB_FORCE_CONTEXT` configuration to force a context

### Features

All features can be enabled/disabled via configuration:
- `OTL_METRICS_FEATURE_HTTP_MIDDLEWARE`
- `OTL_METRICS_FEATURE_QUEUE_JOBS`
- `OTL_METRICS_FEATURE_SCHEDULED_TASKS`
- `OTL_METRICS_FEATURE_PROMETHEUS_SCRAPE`

## Metrics Structure

### HTTP Metrics (Middleware)
- `http_requests_total{method,status}`: Total number of HTTP requests
- `http_request_duration_ms{method,route,status}`: HTTP request duration

### Queue Jobs Metrics
- `queue_jobs_started_total{job,queue}`: Jobs started
- `queue_jobs_completed_total{job,queue}`: Jobs completed successfully
- `queue_jobs_failed_total{job,queue}`: Jobs failed
- `queue_jobs_duration_ms{job,queue,status}`: Jobs duration

### Scheduled Tasks Metrics
- `scheduled_tasks_started_total{command}`: Tasks started
- `scheduled_tasks_completed_total{command}`: Tasks completed successfully
- `scheduled_tasks_failed_total{command}`: Tasks failed
- `scheduled_tasks_duration_ms{command,status}`: Tasks duration

