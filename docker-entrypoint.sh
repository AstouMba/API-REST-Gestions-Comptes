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
fi

# Récupérer le client password **après création**
PASSPORT_PASSWORD_CLIENT_ID=$(php artisan tinker --execute='echo \Laravel\Passport\Client::where("password_client", true)->first()->id;')
PASSPORT_PASSWORD_CLIENT_SECRET=$(php artisan tinker --execute='echo \Laravel\Passport\Client::where("password_client", true)->first()->secret;')

# Exporter pour Laravel et Docker
export PASSPORT_PASSWORD_CLIENT_ID
export PASSPORT_PASSWORD_CLIENT_SECRET

echo "PASSPORT_PASSWORD_CLIENT_ID=$PASSPORT_PASSWORD_CLIENT_ID"
echo "PASSPORT_PASSWORD_CLIENT_SECRET=$PASSPORT_PASSWORD_CLIENT_SECRET"

echo "Starting Laravel..."
exec "$@"
