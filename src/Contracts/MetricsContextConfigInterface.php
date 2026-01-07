<?php

namespace Goopil\OtlpMetrics\Contracts;

interface MetricsContextConfigInterface
{
    public function isEnabled(): bool;

    public function getEndpoint(): string;

    public function getProtocol(): string;

    public function getHeaders(): array;
}
