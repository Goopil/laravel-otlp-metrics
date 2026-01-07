<?php

/**
 * Exemples d'utilisation de la librairie Laravel OTL Metrics
 */

use Goopil\OtlpMetrics\Facades\OtlpContext;
use Goopil\OtlpMetrics\Facades\OtlpMetrics;

// ============================================
// Exemple 1 : Utilisation basique (API/Web)
// ============================================

// Dans un contrôleur
class ExampleController
{
    public function index()
    {
        // Counter
        $counter = OtlpMetrics::counter('api_requests_total', 'Total API requests');
        $counter->add(1, ['endpoint' => '/api/users']);

        // Histogram pour mesurer la durée
        $startTime = microtime(true);
        // ... votre logique ...
        $duration = (microtime(true) - $startTime) * 1000;

        $histogram = OtlpMetrics::histogram('api_request_duration_ms', 'API request duration', 'ms');
        $histogram->record($duration, ['endpoint' => '/api/users']);

        // Gauge
        $gauge = OtlpMetrics::gauge('active_connections', 'Active database connections');
        $gauge->record(10, ['type' => 'database']);
    }
}

// ============================================
// Exemple 2 : Utilisation dans un Worker
// ============================================

class ProcessOrderJob
{
    public function handle()
    {
        $startTime = microtime(true);

        try {
            // Traitement de la commande
            // ...

            $duration = (microtime(true) - $startTime) * 1000;

            $histogram = OtlpMetrics::histogram('order_processing_duration_ms', 'Order processing duration', 'ms');
            $histogram->record($duration, ['status' => 'success']);

            $counter = OtlpMetrics::counter('orders_processed_total', 'Total processed orders');
            $counter->add(1, ['status' => 'success']);
        } catch (\Exception $e) {
            $counter = OtlpMetrics::counter('orders_processed_total', 'Total processed orders');
            $counter->add(1, ['status' => 'failed']);
            throw $e;
        }
    }
}

// ============================================
// Exemple 3 : Utilisation dans un CronJob
// ============================================

class DailyReportCommand
{
    public function handle()
    {
        $counter = OtlpMetrics::counter('daily_reports_generated_total', 'Total daily reports generated');
        $counter->add(1);

        // Génération du rapport
        // ...
    }
}

// ============================================
// Exemple 4 : Détection de contexte
// ============================================

$context = OtlpContext::detect(); // 'api', 'worker', ou 'cronjob'

if (OtlpContext::isWorker()) {
    // Logique spécifique aux workers
}

if (OtlpContext::isCronJob()) {
    // Logique spécifique aux cron jobs
}

// ============================================
// Exemple 5 : Observable metrics
// ============================================

// Observable Counter (mise à jour automatique)
OtlpMetrics::observableCounter('cpu_usage_total', function () {
    $load = sys_getloadavg();

    return $load[0] ?? 0;
}, 'CPU usage');

// Observable Gauge (mise à jour automatique)
OtlpMetrics::observableGauge('memory_usage_bytes', function () {
    return memory_get_usage(true);
}, 'Memory usage in bytes', 'bytes');
