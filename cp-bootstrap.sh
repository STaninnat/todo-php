#!/bin/bash
# copy-bootstrap.sh
# Script for local dev: copy Bootstrap CSS + JS to public/assets

mkdir -p public/assets/css public/assets/js

# Copy CSS
if [ -f vendor/twbs/bootstrap/dist/css/bootstrap.min.css ]; then
    cp vendor/twbs/bootstrap/dist/css/bootstrap.min.css public/assets/css/
    echo "Copied bootstrap.min.css → public/assets/css/"
else
    echo "Error: vendor/twbs/bootstrap/dist/css/bootstrap.min.css not found!"
fi

# Copy JS
if [ -f vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js ]; then
    cp vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js public/assets/js/
    echo "Copied bootstrap.bundle.min.js → public/assets/js/"
else
    echo "Error: vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js not found!"
fi

echo "Bootstrap copy completed!"
