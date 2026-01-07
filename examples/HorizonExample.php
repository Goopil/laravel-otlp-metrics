<?php

/**
 * Examples of using Laravel Horizon metrics tracking
 *
 * Note: This file contains example code snippets that should be used
 * in your actual Laravel application files, not executed directly.
 */

// ============================================
// Example 1: Manual collection of Horizon metrics
// ============================================
//
// In a scheduled task or command:
//
// $tracker = app(\Goopil\OtlpMetrics\Services\HorizonMetricsTracker::class);
//
// if ($tracker->isHorizonAvailable()) {
//     $tracker->collectMetrics();
// }

// ============================================
// Example 2: Using in a scheduled task
// ============================================
//
// In app/Console/Kernel.php:
//
// protected function schedule(Schedule $schedule): void
// {
//     // Collect Horizon metrics every minute
//     $schedule->call(function () {
//         $tracker = app(\Goopil\OtlpMetrics\Services\HorizonMetricsTracker::class);
//         if ($tracker->isHorizonAvailable()) {
//             $tracker->collectMetrics();
//         }
//     })->everyMinute();
// }

// ============================================
// Example 3: Available Horizon metrics
// ============================================
//
// The following metrics are automatically collected:
// - horizon_queue_pending{queue} - Number of pending jobs
// - horizon_queue_processing{queue} - Number of processing jobs
// - horizon_queue_completed{queue} - Number of completed jobs
// - horizon_queue_failed{queue} - Number of failed jobs
// - horizon_job_throughput{queue} - Jobs processed per minute
// - horizon_job_wait_time_ms{queue} - Job wait time in milliseconds
// - horizon_job_runtime_ms{queue} - Job runtime in milliseconds
// - horizon_workers_total - Total number of Horizon workers
// - horizon_processes_total - Total number of Horizon processes
// - horizon_queue_wait_time_ms{queue} - Average wait time for queue
// - horizon_throughput_jobs_per_minute{queue} - Jobs processed per minute

// ============================================
// Example 4: Configuration
// ============================================
//
// In .env:
// OTL_METRICS_FEATURE_HORIZON=true
// OTL_METRICS_HORIZON_ENABLED=true
// OTL_METRICS_HORIZON_COLLECTION_INTERVAL=60
