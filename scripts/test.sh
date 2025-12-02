#!/bin/bash
set -e

MODE="full"      # full = unit + integration
FAST=false

# Check for required tools
if ! command -v xmllint &> /dev/null; then
  echo "Error: xmllint is not installed. Please install libxml2-utils (Debian/Ubuntu) or libxml2 (Alpine)."
  exit 1
fi

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
    e2e)
      MODE="e2e"
      ;;
  esac
done

echo "Running tests..."
echo "Mode: $MODE | Fast: $FAST"
echo "-----------------------------------------"

UNIT_PASSED=0
UNIT_FAILED=0
INTEGRATION_PASSED=0
INTEGRATION_FAILED=0
E2E_PASSED=0
E2E_FAILED=0

# Function to run PHPUnit and count pass/failed from exit code.
run_phpunit() {
  local CONFIG_FILE=$1
  local NAME=$2
  local TEMP_XML=$(mktemp)

  # Run PHPUnit (show output normally, keep XML report)
  STATUS=0
  backend/vendor/bin/phpunit -c "$CONFIG_FILE" --log-junit "$TEMP_XML" || STATUS=$?

  # Read XML report and sum nested testsuites
  TESTS=$(xmllint --xpath "sum(/testsuites/testsuite/@tests)" "$TEMP_XML")
  FAILURES=$(xmllint --xpath "sum(/testsuites/testsuite/@failures)" "$TEMP_XML")
  ERRORS=$(xmllint --xpath "sum(/testsuites/testsuite/@errors)" "$TEMP_XML")
  FAILED=$((FAILURES + ERRORS))
  PASSED=$((TESTS - FAILED))

 # Store the number in a global variable
  if [ "$NAME" = "unit" ]; then
    UNIT_PASSED=$PASSED
    UNIT_FAILED=$FAILED
  elif [ "$NAME" = "integration" ]; then
    INTEGRATION_PASSED=$PASSED
    INTEGRATION_FAILED=$FAILED
  else
    E2E_PASSED=$PASSED
    E2E_FAILED=$FAILED
  fi

  echo ""

  # Show a short summary
  if [ $STATUS -eq 0 ]; then
    echo "$NAME tests: PASSED ($PASSED/$TESTS)"
  else
    echo "$NAME tests: FAILED ($PASSED/$TESTS passed, $FAILED failed)"
  fi

  # Return PHPUnit exit code
  return $STATUS
}

run_unit() {
  echo "Running unit tests..."
  run_phpunit backend/phpunit.unit.xml.dist unit
  echo ""
}

# Run integration tests
run_integration() {
  echo "Running integration tests..."
  STATUS=0

  if [ ! -f ".env.test" ] && [ ! -f "../.env.test" ]; then
      echo "Error: .env.test file not found in current or parent directory."
      exit 1
  fi

  # Temp folder on host to store XML
  TEMP_DIR=$(mktemp -d)
  TEMP_XML="$TEMP_DIR/integration.xml"

  if [ "$FAST" = true ]; then
    echo "Fast mode: using existing docker containers"

    # Mount temp folder to /tmp in container
    docker compose --profile test -f docker-compose.test.yml run --rm \
      -v "$TEMP_DIR":/tmp php-fpm \
      vendor/bin/phpunit -c phpunit.integration.xml.dist --log-junit /tmp/integration.xml || STATUS=$?

  else
    echo "Starting test containers..."
    docker compose --profile test -f docker-compose.test.yml up -d

    docker compose --profile test -f docker-compose.test.yml run --rm \
      -v "$TEMP_DIR":/tmp php-fpm \
      vendor/bin/phpunit -c phpunit.integration.xml.dist --log-junit /tmp/integration.xml || STATUS=$?

    echo "Stopping test containers..."
    docker compose --profile test -f docker-compose.test.yml down -v
  fi

  # Read XML report from host temp folder
  if [ -f "$TEMP_XML" ]; then
    TESTS=$(xmllint --xpath "sum(/testsuites/testsuite/@tests)" "$TEMP_XML")
    FAILURES=$(xmllint --xpath "sum(/testsuites/testsuite/@failures)" "$TEMP_XML")
    ERRORS=$(xmllint --xpath "sum(/testsuites/testsuite/@errors)" "$TEMP_XML")
    FAILED=$((FAILURES + ERRORS))
    PASSED=$((TESTS - FAILED))
  else
    TESTS=0
    FAILED=0
    PASSED=0
  fi

  INTEGRATION_PASSED=$PASSED
  INTEGRATION_FAILED=$FAILED

  echo ""
  if [ $STATUS -eq 0 ]; then
    echo "Integration tests: PASSED ($PASSED/$TESTS)"
  else
    echo "Integration tests: FAILED ($PASSED/$TESTS passed, $FAILED failed)"
  fi
  echo ""
}

# Run E2E tests
run_e2e() {
  echo "Running E2E tests..."
  STATUS=0

  if [ ! -f ".env.test" ] && [ ! -f "../.env.test" ]; then
      echo "Error: .env.test file not found in current or parent directory."
      exit 1
  fi

  # Temp folder on host to store XML
  TEMP_DIR=$(mktemp -d)
  TEMP_XML="$TEMP_DIR/e2e.xml"

  echo "Starting test containers..."
  docker compose --profile test -f docker-compose.test.yml up -d

  # Ensure migrations run via bootstrap, which happens when phpunit runs
  docker compose --profile test -f docker-compose.test.yml run --rm \
    -v "$TEMP_DIR":/tmp php-fpm \
    vendor/bin/phpunit -c phpunit.e2e.xml --log-junit /tmp/e2e.xml || STATUS=$?

  echo "Stopping test containers..."
  docker compose --profile test -f docker-compose.test.yml down -v

  # Read XML report from host temp folder
  if [ -f "$TEMP_XML" ]; then
    TESTS=$(xmllint --xpath "sum(/testsuites/testsuite/@tests)" "$TEMP_XML")
    FAILURES=$(xmllint --xpath "sum(/testsuites/testsuite/@failures)" "$TEMP_XML")
    ERRORS=$(xmllint --xpath "sum(/testsuites/testsuite/@errors)" "$TEMP_XML")
    FAILED=$((FAILURES + ERRORS))
    PASSED=$((TESTS - FAILED))
  else
    TESTS=0
    FAILED=0
    PASSED=0
  fi

  E2E_PASSED=$PASSED
  E2E_FAILED=$FAILED

  echo ""
  if [ $STATUS -eq 0 ]; then
    echo "E2E tests: PASSED ($PASSED/$TESTS)"
  else
    echo "E2E tests: FAILED ($PASSED/$TESTS passed, $FAILED failed)"
  fi
  echo ""
}

EXIT_CODE=0

case $MODE in
  full)
    run_unit || EXIT_CODE=$?
    run_integration || EXIT_CODE=$?
    run_e2e || EXIT_CODE=$?
    ;;
  unit)
    run_unit || EXIT_CODE=$?
    ;;
  integration)
    run_integration || EXIT_CODE=$?
    ;;
  e2e)
    run_e2e || EXIT_CODE=$?
    ;;
esac

# Summary in numerical form
echo "-----------------------------------------"
echo "Summary:"
if [ "$MODE" = "unit" ] || [ "$MODE" = "full" ]; then
  echo "Unit tests: $UNIT_PASSED passed, $UNIT_FAILED failed"
fi
if [ "$MODE" = "integration" ] || [ "$MODE" = "full" ]; then
  echo "Integration tests: $INTEGRATION_PASSED passed, $INTEGRATION_FAILED failed"
fi
if [ "$MODE" = "e2e" ] || [ "$MODE" = "full" ]; then
  echo "E2E tests: $E2E_PASSED passed, $E2E_FAILED failed"
fi
echo "All done!"

# Clean up temp folder
rm -rf "$TEMP_DIR"

exit $EXIT_CODE
