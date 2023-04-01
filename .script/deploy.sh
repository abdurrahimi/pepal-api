#!/bin/bash
set -e

echo "Deployment started ..."

# Enter maintenance mode or return true
# if already is in maintenance mode
(php artisan down) || true

# Pull the latest version of the app
git stash

git pull origin master

# Clear the old cache
php artisan clear-compiled

# Recreate cache
php artisan optimize

# Exit maintenance mode
php artisan up

echo "Deployment finished!"
