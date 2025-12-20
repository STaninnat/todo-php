#!/bin/bash
set -e

# Check if the first argument is "images" for selective image removal
if [ "$1" = "images" ]; then
  shift
  if [ -z "$1" ]; then
    echo "Usage: ./scripts/clean.sh images [image1] [image2] ..."
    exit 1
  fi
  echo "Removing specified images: $@"
  docker rmi "$@" || echo "Warning: Some images could not be removed (container running or image not found)."
  exit 0
fi

echo "Cleaning all Docker environments..."

./scripts/dev.sh down:clean
./scripts/dev.sh down:clean "$@"

echo "Removing unused Docker data..."
docker system prune -f --volumes

echo "Clean complete!"
