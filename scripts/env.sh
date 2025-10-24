#!/bin/bash
set -e

COMMAND=$1
PROFILE=$2

if [ -z "$COMMAND" ]; then
  echo "Usage: ./scripts/env.sh [build|up|rebuild|down|down:clean] [test]"
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

case $COMMAND in
  build)
    echo "Building containers..."
    docker compose $PROFILE_ARG -f "$FILE" build
    ;;
  up)
    echo "Starting containers..."
    docker compose $PROFILE_ARG -f "$FILE" up -d
    ;;
  rebuild)
    echo "Rebuilding containers..."
    docker compose $PROFILE_ARG -f "$FILE" up -d --build
    ;;
  down)
    echo "Stopping containers..."
    docker compose $PROFILE_ARG -f "$FILE" down
    ;;
  down:clean)
    echo "Removing containers, volumes, and images..."
    docker compose $PROFILE_ARG -f "$FILE" down -v --rmi all
    ;;
  *)
    echo "Unknown command: $COMMAND"
    exit 1
    ;;
esac

echo "Done!"
