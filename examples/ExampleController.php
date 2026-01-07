<?php

namespace App\Http\Controllers;

use Goopil\OtlpMetrics\Facades\OtlpMetrics;
use Illuminate\Http\Request;

class ExampleController extends Controller
{
    public function index(Request $request)
    {
        // Exemple d'utilisation d'un counter
        $counter = OtlpMetrics::counter('api_requests_total', 'Total API requests');
        $counter->add(1, [
            'endpoint' => '/api/example',
            'method' => $request->method(),
        ]);

        // Exemple d'utilisation d'un histogram pour mesurer la durée
        $startTime = microtime(true);

        // Votre logique métier ici
        $data = ['message' => 'Hello from Laravel OTL Metrics'];

        $duration = (microtime(true) - $startTime) * 1000; // en millisecondes

        $histogram = OtlpMetrics::histogram('api_request_duration_ms', 'API request duration', 'ms');
        $histogram->record($duration, [
            'endpoint' => '/api/example',
        ]);

        return response()->json($data);
    }

    public function metrics()
    {
        // Exemple avec un gauge pour les connexions actives
        $gauge = OtlpMetrics::gauge('active_connections', 'Active database connections');
        $gauge->record(10, ['type' => 'database']);

        // Exemple avec un observable counter pour le CPU
        OtlpMetrics::observableCounter('cpu_usage_total', function () {
            $load = sys_getloadavg();

            return $load[0] ?? 0;
        }, 'CPU usage');

        return response()->json(['status' => 'ok']);
    }
}
