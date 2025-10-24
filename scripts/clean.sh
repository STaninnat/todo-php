#!/bin/bash
set -e

echo "Cleaning all Docker environments..."

./scripts/env.sh down:clean
./scripts/env.sh down:clean test

echo "Removing unused Docker data..."
docker system prune -f --volumes

echo "Clean complete!"
