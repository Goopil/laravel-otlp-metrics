<?php

namespace Goopil\OtlpMetrics\Tests\Unit;

use Goopil\OtlpMetrics\Enums\Protocol;
use Goopil\OtlpMetrics\Exceptions\ProtocolException;
use Goopil\OtlpMetrics\Tests\TestCase;

class ProtocolEnumTest extends TestCase
{
    public function test_protocol_from_string(): void
    {
        $this->assertEquals(Protocol::GRPC, Protocol::fromString('grpc'));
        $this->assertEquals(Protocol::HTTP_PROTOBUF, Protocol::fromString('http/protobuf'));
        $this->assertEquals(Protocol::HTTP_JSON, Protocol::fromString('http/json'));
        $this->assertEquals(Protocol::HTTP_PROTOBUF, Protocol::fromString('HTTP/PROTOBUF'));
    }

    public function test_protocol_from_string_throws_exception_for_invalid_value(): void
    {
        $this->expectException(ProtocolException::class);
        $this->expectExceptionMessage('Unsupported protocol: invalid. Supported: grpc, http/protobuf, http/json');

        Protocol::fromString('invalid');
    }

    public function test_protocol_values(): void
    {
        $this->assertEquals('grpc', Protocol::GRPC->value);
        $this->assertEquals('http/protobuf', Protocol::HTTP_PROTOBUF->value);
        $this->assertEquals('http/json', Protocol::HTTP_JSON->value);
    }
}
