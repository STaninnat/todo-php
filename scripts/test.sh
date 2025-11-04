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
echo "-----------------------------------------"

UNIT_PASSED=0
UNIT_FAILED=0
INTEGRATION_PASSED=0
INTEGRATION_FAILED=0

# Function to run PHPUnit and count pass/failed from exit code.
run_phpunit() {
  local CONFIG_FILE=$1
  local NAME=$2
  local TEMP_XML=$(mktemp)

  # Run PHPUnit (show output normally, keep XML report)
  STATUS=0
  vendor/bin/phpunit -c "$CONFIG_FILE" --log-junit "$TEMP_XML" || STATUS=$?

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
  else
    INTEGRATION_PASSED=$PASSED
    INTEGRATION_FAILED=$FAILED
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
  run_phpunit phpunit.unit.xml.dist unit
  echo ""
}

# Run integration tests
run_integration() {
  echo "Running integration tests..."
  STATUS=0

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
    docker compose --profile test -f docker-compose.test.yml down
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

EXIT_CODE=0

case $MODE in
  full)
    run_unit || EXIT_CODE=$?
    run_integration || EXIT_CODE=$?
    ;;
  unit)
    run_unit || EXIT_CODE=$?
    ;;
  integration)
    run_integration || EXIT_CODE=$?
    ;;
esac

# Summary in numerical form
echo "-----------------------------------------"
echo "Summary:"
if [ "$MODE" != "integration" ]; then
  echo "Unit tests: $UNIT_PASSED passed, $UNIT_FAILED failed"
fi
if [ "$MODE" != "unit" ]; then
  echo "Integration tests: $INTEGRATION_PASSED passed, $INTEGRATION_FAILED failed"
fi
echo "All done!"

# Clean up temp folder
rm -rf "$TEMP_DIR"

exit $EXIT_CODE
