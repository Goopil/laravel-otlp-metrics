<?php

namespace Goopil\OtlpMetrics\Contracts;

interface ValidatableConfigInterface
{
    /**
     * Validate the configuration
     *
     * @throws \InvalidArgumentException
     */
    public function validate(): void;
}
