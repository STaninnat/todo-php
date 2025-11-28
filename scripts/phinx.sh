#!/bin/bash
set -e

COMMAND=$1
PROFILE=$2
if [ -n "$PROFILE" ]; then
  shift 2
else
  shift 1
fi

if [ -z "$COMMAND" ]; then
  echo "Usage: ./scripts/phinx.sh [migrate|rollback|create|seed:create|seed:run] [test] [extra phinx args]"
  exit 1
fi

FILE="docker-compose.yml"
PROFILE_ARG=""

if [ "$PROFILE" = "test" ]; then
  FILE="docker-compose.test.yml"
  PROFILE_ARG="--profile test"
  echo "Using test environment..."
else
  echo "Using main environment..."
fi

run_phinx() {
    local ACTION=$1
    shift
    echo "Running phinx: $ACTION $*"
    docker compose $PROFILE_ARG -f "$FILE" exec -T php-fpm vendor/bin/phinx "$ACTION" "$@"
    echo "Phinx $ACTION finished!"
}

case "$COMMAND" in
  migrate)
    run_phinx migrate "$@"
    ;;
  rollback)
    run_phinx rollback "$@"
    ;;
  create)
    run_phinx create "$@"
    ;;
  seed:create)
    run_phinx seed:create "$@"
    ;;
  seed:run)
    run_phinx seed:run "$@"
    ;;
  *)
    echo "Unknown command: $COMMAND"
    echo "Usage: ./scripts/phinx.sh [migrate|rollback|create|seed:create|seed:run] [test] [extra phinx args]"
    exit 1
    ;;
esac