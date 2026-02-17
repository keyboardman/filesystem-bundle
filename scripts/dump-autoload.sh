#!/bin/sh
set -e

echo "Optimizing Composer autoloader..."

composer dump-autoload --optimize

echo "Autoloader optimized successfully."
