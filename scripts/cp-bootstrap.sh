#!/bin/bash
# copy-bootstrap.sh
# Script for local dev: copy Bootstrap CSS + JS to public/assets
# Works regardless of current working directory

# Find the path of the script file.
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

ROOT_DIR="$SCRIPT_DIR/.."

mkdir -p "$ROOT_DIR/public/assets/css" "$ROOT_DIR/public/assets/js"

# Source and Destination
CSS_SRC="$ROOT_DIR/vendor/twbs/bootstrap/dist/css/bootstrap.min.css"
CSS_DEST="$ROOT_DIR/public/assets/css/"

JS_SRC="$ROOT_DIR/vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js"
JS_DEST="$ROOT_DIR/public/assets/js/"

# Copy CSS
if [ -f "$CSS_SRC" ]; then
    cp "$CSS_SRC" "$CSS_DEST"
    echo "Copied bootstrap.min.css → public/assets/css/"
else
    echo "Error: $CSS_SRC not found!"
fi

# Copy JS
if [ -f "$JS_SRC" ]; then
    cp "$JS_SRC" "$JS_DEST"
    echo "Copied bootstrap.bundle.min.js → public/assets/js/"
else
    echo "Error: $JS_SRC not found!"
fi

echo "Bootstrap copy completed!"
