<?php

/**
 * Examples of using the AttributeService service
 */

use Goopil\OtlpMetrics\Facades\OtlpAttributes;
use Goopil\OtlpMetrics\Facades\OtlpMetrics;

// ============================================
// Example 1: Using Attributes Facade
// ============================================

// Add attributes that will be applied to all metrics in the current scope
OtlpAttributes::addAttribute('environment', 'production');
OtlpAttributes::addAttribute('version', '1.0.0');

// Add multiple attributes at once
OtlpAttributes::addAttributes([
    'region' => 'us-east-1',
    'user_id' => '12345',
]);

// When recording metrics, scope attributes are automatically merged
$counter = OtlpMetrics::counter('api_requests_total', 'Total API requests');
$counter->add(1, ['endpoint' => '/api/users']);
// This will include: environment, version, region, user_id, endpoint, etc.

// ============================================
// Example 2: Using AttributeService via Dependency Injection
// ============================================

class MyController
{
    public function __construct(
        protected \Goopil\OtlpMetrics\Support\Metrics\AttributeService $attributeManager
    ) {}

    public function index()
    {
        // Add attributes for this request scope
        $this->attributeManager->addAttributes([
            'route' => 'users.index',
            'method' => 'GET',
        ]);

        // Record metrics - scope attributes will be automatically merged
        $counter = OtlpMetrics::counter('page_views');
        $counter->add(1);
    }
}

// ============================================
// Example 3: Binding Scope
// ============================================

// Because AttributeService is bound as "scoped", attributes added in a Middleware
// or during a Job execution will only exist for that specific execution and will
// be automatically cleared when the next request/job starts (in persistent environments).

// ============================================
// Example 4: Manual attribute merging
// ============================================

$mergedAttributes = OtlpAttributes::mergeAttributes(['custom' => 'value']);
// Returns: all scope attributes + custom attributes
