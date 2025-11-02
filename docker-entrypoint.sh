#!/bin/sh

echo "Waiting for database..."
while ! pg_isready -h $DB_HOST -p $DB_PORT -U $DB_USERNAME; do
  echo "Database unavailable, sleeping..."
  sleep 1
done

echo "Database ready, running migrations..."
php artisan migrate --force

# Vérifier si un client password existe
CLIENT_EXISTS=$(php artisan tinker --execute='echo \Laravel\Passport\Client::where("password_client", true)->count();')

if [ "$CLIENT_EXISTS" -eq 0 ]; then
  echo "Creating Passport clients..."
  php artisan passport:install --force
else
  echo "Passport clients already exist, skipping creation..."
fi

echo "Starting Laravel..."
exec "$@"