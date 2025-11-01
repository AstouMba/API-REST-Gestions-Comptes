#!/bin/sh

echo "Waiting for database to be ready..."
while ! pg_isready -h $DB_HOST -p $DB_PORT -U $DB_USERNAME; do
  echo "Database is unavailable - sleeping"
  sleep 1
done

echo "Database is up - executing migrations"
php artisan migrate --force

# Vérifier si un client password existe
if [ -z "$(php artisan tinker --execute='echo \App\Models\OauthClient::where("password_client", true)->first()->id ?? ""')" ]; then
  echo "Installing Passport clients..."
  php artisan passport:install --force
fi

# Récupérer le client password
PASSPORT_PASSWORD_CLIENT_ID=$(php artisan tinker --execute='echo \App\Models\OauthClient::where("password_client", true)->first()->id;')
PASSPORT_PASSWORD_CLIENT_SECRET=$(php artisan tinker --execute='echo \App\Models\OauthClient::where("password_client", true)->first()->secret;')

export PASSPORT_PASSWORD_CLIENT_ID
export PASSPORT_PASSWORD_CLIENT_SECRET

echo "PASSPORT_PASSWORD_CLIENT_ID=$PASSPORT_PASSWORD_CLIENT_ID"
echo "PASSPORT_PASSWORD_CLIENT_SECRET=$PASSPORT_PASSWORD_CLIENT_SECRET"

echo "Starting Laravel application..."
exec "$@"
