#!/bin/sh
set -e

php artisan config:clear
php artisan migrate --force
php artisan db:seed --class=AdminUserSeeder --force

# One-off: the three new puzzles. Delete this line after the first successful
# deploy so a puzzle you later delete doesn't come back on the next deploy.
php artisan db:seed --class=NewPuzzlesSeeder --force

php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
