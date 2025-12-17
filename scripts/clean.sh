#!/bin/bash
set -e

echo "Cleaning all Docker environments..."

./scripts/dev.sh down:clean
./scripts/dev.sh down:clean "$@"

echo "Removing unused Docker data..."
docker system prune -f --volumes

echo "Clean complete!"
