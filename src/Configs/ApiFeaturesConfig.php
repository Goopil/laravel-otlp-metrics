<?php

namespace Goopil\OtlpMetrics\Configs;

class ApiFeaturesConfig
{
    public function __construct() {}

    public static function fromArray(array $config): self
    {
        return new self();
    }
}
