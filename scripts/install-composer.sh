#!/bin/sh
set -e

echo "Installing Composer dependencies..."

composer install --no-interaction --no-scripts --prefer-dist

echo "Composer dependencies installed successfully."
