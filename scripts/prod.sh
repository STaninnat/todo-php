#!/bin/bash
set -e

COMMAND=$1

if [ -z "$COMMAND" ]; then
  echo "Usage: ./scripts/prod.sh [up|down|build|logs]"
  exit 1
fi

# Use docker-compose.yml as base, and prod override
FILES="-f docker-compose.yml -f docker-compose.prod.yml"

echo "Executing: docker compose $FILES $COMMAND ..."

case $COMMAND in
  up)
    docker compose $FILES up -d
    ;;
  down)
    docker compose $FILES down
    ;;
  build)
    docker compose $FILES build
    ;;
  logs)
    docker compose $FILES logs -f
    ;;
  *)
    # Pass through other commands
    docker compose $FILES $@
    ;;
esac
