#!/bin/bash
set -e

# Render provides the PORT env var (default 10000 if not set)
PORT=${PORT:-8080}

echo "Starting deployment on port $PORT..."

# Replace the 'listen 8080' directive in nginx config with the actual PORT
sed -i "s/listen 8080;/listen $PORT;/g" /etc/nginx/nginx.conf

# Start Supervisor (which starts Nginx and PHP-FPM)
exec /usr/bin/supervisord -c /etc/supervisord.conf
