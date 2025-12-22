#!/bin/bash
set -e

COMMAND=$1

if [ -z "$COMMAND" ]; then
  echo "Usage: ./scripts/render.sh [up|down|build|rebuild|logs|shell]"
  exit 1
fi

FILES="-f docker-compose.render.yml"

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
  rebuild)
    docker compose $FILES up -d --build
    ;;
  logs)
    docker compose $FILES logs -f
    ;;
  shell)
    docker exec -it backend_deploy /bin/sh
    ;;
  *)
    docker compose $FILES $@
    ;;
esac
