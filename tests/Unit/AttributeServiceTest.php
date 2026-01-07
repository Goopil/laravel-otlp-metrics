<?php

namespace Goopil\OtlpMetrics\Tests\Unit;

use Goopil\OtlpMetrics\Support\Metrics\AttributeService;
use Goopil\OtlpMetrics\Tests\TestCase;

class AttributeServiceTest extends TestCase
{
    protected AttributeService $attributeManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->attributeManager = new AttributeService();
    }

    public function test_can_add_and_get_attributes(): void
    {
        $this->attributeManager->addAttribute('env', 'production');
        $this->attributeManager->addAttributes(['service' => 'api', 'version' => '1.0']);

        $expected = [
            'env' => 'production',
            'service' => 'api',
            'version' => '1.0',
        ];

        $attributes = $this->attributeManager->getAttributes();
        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $attributes[$key]);
        }
    }

    public function test_can_remove_and_clear_attributes(): void
    {
        $this->attributeManager->addAttributes(['a' => '1', 'b' => '2']);
        $this->attributeManager->removeAttribute('a');

        $this->assertEquals('2', $this->attributeManager->getAttributes()['b']);
        $this->assertArrayNotHasKey('a', $this->attributeManager->getAttributes());

        $this->attributeManager->clear();
        $this->assertEmpty($this->attributeManager->getAttributes());
    }

    public function test_merge_attributes_priority(): void
    {
        $this->attributeManager->addAttribute('common', 'initial');
        $this->attributeManager->addAttribute('override', 'initial');

        $provided1 = ['provided1' => 'v1', 'override' => 'v1'];
        $provided2 = ['provided2' => 'v2', 'override' => 'v2'];

        $merged = $this->attributeManager->mergeAttributes($provided1, $provided2);

        $this->assertEquals('initial', $merged['common']);
        $this->assertEquals('v1', $merged['provided1']);
        $this->assertEquals('v2', $merged['provided2']);
        $this->assertEquals('v2', $merged['override']); // Last one should override
    }

    public function test_get_all_attributes_alias(): void
    {
        $this->attributeManager->addAttribute('g', '1');

        $all = $this->attributeManager->getAllAttributes();
        $this->assertEquals('1', $all['g']);
        $this->assertEquals($this->attributeManager->getAttributes(), $all);
    }
}
