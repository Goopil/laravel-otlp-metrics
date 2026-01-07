<?php

namespace Goopil\OtlpMetrics\Tests\Feature;

use Goopil\OtlpMetrics\Facades\OtlpAttributes;
use Goopil\OtlpMetrics\Support\Metrics\AttributeService;
use Goopil\OtlpMetrics\Tests\TestCase;
use Illuminate\Support\Facades\Context;

class ScopedAttributesTest extends TestCase
{
    public function test_attributes_are_scoped_to_the_execution_context(): void
    {
        // 1. Simulate first scope
        $instance1 = app(AttributeService::class);
        $instance1->addAttribute('request_id', 'req-1');

        $attributes = $instance1->getAttributes();
        $this->assertArrayHasKey('request_id', $attributes);
        $this->assertEquals('req-1', $attributes['request_id']);
        $this->assertArrayHasKey('service.context', $attributes);

        // In Laravel, forgetScoped() can be used to simulate end of scope
        app()->forgetScopedInstances();

        // 2. Simulate second scope
        $instance2 = app(AttributeService::class);

        $this->assertNotSame($instance1, $instance2, 'Scoped service should provide a new instance after reset');
        $this->assertArrayNotHasKey('request_id', $instance2->getAttributes(), 'Attributes from previous scope should not be present');
    }

    public function test_facade_uses_scoped_instance(): void
    {
        OtlpAttributes::addAttribute('user_id', '123');
        $this->assertEquals('123', app(AttributeService::class)->getAttributes()['user_id']);

        app()->forgetScopedInstances();

        $this->assertArrayNotHasKey('user_id', app(AttributeService::class)->getAttributes());
    }

    public function test_service_context_is_preserved_across_scopes_via_rebind(): void
    {
        // The service.context attribute is added in the ServiceProvider's scoped closure.
        // It should be present in every new scope.

        $instance1 = app(AttributeService::class);
        $this->assertArrayHasKey('service.context', $instance1->getAttributes());
        $contextValue = $instance1->getAttributes()['service.context'];

        app()->forgetScopedInstances();

        $instance2 = app(AttributeService::class);
        $this->assertArrayHasKey('service.context', $instance2->getAttributes());
        $this->assertEquals($contextValue, $instance2->getAttributes()['service.context']);
    }
}
