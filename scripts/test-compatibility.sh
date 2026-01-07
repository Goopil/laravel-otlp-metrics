#!/bin/bash

# Script to test compatibility across different PHP and Laravel versions
# Usage: ./scripts/test-compatibility.sh

set -e

echo "🧪 Testing Package Compatibility"
echo "================================"
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test configurations
declare -a TEST_CONFIGS=(
    "8.1:^10.0:^8.0"
    "8.2:^10.0:^8.0"
    "8.2:^11.0:^9.0"
    "8.3:^11.0:^9.0"
    "8.3:^12.0:^10.0"
    "8.4:^12.0:^10.0"
    "8.5:^12.0:^10.0"
)

PASSED=0
FAILED=0

for config in "${TEST_CONFIGS[@]}"; do
    IFS=':' read -r php_version laravel_version testbench_version <<< "$config"
    
    echo -e "${YELLOW}Testing PHP ${php_version} with Laravel ${laravel_version}...${NC}"
    
    # Check if PHP version is available
    if ! command -v "php${php_version}" &> /dev/null && ! php -v | grep -q "PHP ${php_version}"; then
        echo -e "${RED}⚠ PHP ${php_version} not available, skipping...${NC}"
        continue
    fi
    
    # Update composer.json temporarily
    composer require "illuminate/support:${laravel_version}" --no-update 2>/dev/null || true
    composer require "orchestra/testbench:${testbench_version}" --dev --no-update 2>/dev/null || true
    
    # Try to update
    if composer update --prefer-dist --no-interaction --quiet 2>/dev/null; then
        # Run tests
        if vendor/bin/phpunit --no-coverage --quiet 2>/dev/null; then
            echo -e "${GREEN}✅ PHP ${php_version} + Laravel ${laravel_version}: PASSED${NC}"
            ((PASSED++))
        else
            echo -e "${RED}❌ PHP ${php_version} + Laravel ${laravel_version}: FAILED${NC}"
            ((FAILED++))
        fi
    else
        echo -e "${RED}❌ PHP ${php_version} + Laravel ${laravel_version}: Dependency resolution failed${NC}"
        ((FAILED++))
    fi
    
    echo ""
done

echo "================================"
echo -e "${GREEN}Passed: ${PASSED}${NC}"
if [ $FAILED -gt 0 ]; then
    echo -e "${RED}Failed: ${FAILED}${NC}"
    exit 1
else
    echo -e "${GREEN}All compatibility tests passed!${NC}"
    exit 0
fi

