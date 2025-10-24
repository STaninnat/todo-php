#!/bin/bash
set -e

MODE="full"      # full = unit + integration
FAST=false

# Parse arguments
for arg in "$@"; do
  case $arg in
    --fast)
      FAST=true
      ;;
    unit)
      MODE="unit"
      ;;
    integration)
      MODE="integration"
      ;;
  esac
done

echo "Running tests..."
echo "Mode: $MODE | Fast: $FAST"
echo "-----------------------------------"

# Run all tests
if [ "$MODE" = "full" ]; then
  echo "Running all tests (unit + integration)..."
  vendor/bin/phpunit -c phpunit.xml.dist
  echo ""
fi

# Run unit tests
if [ "$MODE" = "unit" ]; then
  echo "Running unit tests..."
  vendor/bin/phpunit -c phpunit.unit.xml.dist
  echo ""
fi

# Run integration tests
if [ "$MODE" = "integration" ]; then
  echo "Running integration tests..."
  if [ "$FAST" = true ]; then
    echo "Fast mode: using existing docker containers"
    docker compose --profile test -f docker-compose.test.yml exec php-fpm \
      vendor/bin/phpunit -c phpunit.integration.xml.dist
  else
    echo "Starting test containers..."
    docker compose --profile test -f docker-compose.test.yml up -d

    docker compose --profile test -f docker-compose.test.yml run --rm php-fpm \
      vendor/bin/phpunit -c phpunit.integration.xml.dist

    echo "Stopping test containers..."
    docker compose --profile test -f docker-compose.test.yml down
  fi
  echo ""
fi

echo "All done!"
