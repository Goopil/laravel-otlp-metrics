<?php

namespace Goopil\OtlpMetrics\Enums;

use Goopil\OtlpMetrics\Exceptions\ProtocolException;
use Goopil\OtlpMetrics\Exceptions\TransportException;
use OpenTelemetry\Contrib\Grpc\GrpcTransportFactory;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\SDK\Common\Export\TransportInterface;

enum Protocol: string
{
    case GRPC = 'grpc';
    case HTTP_PROTOBUF = 'http/protobuf';
    case HTTP_JSON = 'http/json';

    /**
     * Create a transport for the protocol
     */
    public function createTransport(string $endpoint, array $headers = [], ?int $timeout = null): TransportInterface
    {
        $this->validateRequirements();

        try {
            $factory = match ($this) {
                self::GRPC => new GrpcTransportFactory(),
                self::HTTP_PROTOBUF, self::HTTP_JSON => new OtlpHttpTransportFactory(),
            };

            if ($this === self::GRPC && ! str_contains($endpoint, 'Service/Export')) {
                // Append the default OTLP metrics service path if not present
                $endpoint = rtrim($endpoint, '/').'/opentelemetry.proto.collector.metrics.v1.MetricsService/Export';
            }

            $contentType = match ($this) {
                self::HTTP_JSON => 'application/json',
                default => 'application/x-protobuf',
            };

            return $factory->create(
                $endpoint,
                $contentType,
                $headers,
                null,
                $timeout
            );
        } catch (\Exception $e) {
            throw new TransportException("Failed to create transport for protocol '{$this->value}': ".$e->getMessage(), 0, $e);
        }
    }

    /**
     * Create a Protocol from a string
     */
    public static function fromString(string $protocol): self
    {
        return self::tryFrom(strtolower($protocol))
            ?? throw new ProtocolException("Unsupported protocol: {$protocol}. Supported: grpc, http/protobuf, http/json");
    }

    /**
     * Validate that all requirements for the protocol are met
     */
    public function validateRequirements(): void
    {
        if ($this === self::GRPC) {
            if (! extension_loaded('grpc')) {
                throw new ProtocolException("The 'grpc' PHP extension is required for gRPC transport. Please install and enable it.");
            }

            if (! class_exists(GrpcTransportFactory::class)) {
                throw new ProtocolException("The 'open-telemetry/transport-grpc' package is required for gRPC transport. Please install it via composer.");
            }
        }
    }
}
