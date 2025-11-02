#!/bin/sh

echo "Waiting for database..."
while ! pg_isready -h $DB_HOST -p $DB_PORT -U $DB_USERNAME; do
  echo "Database unavailable, sleeping..."
  sleep 1
done

echo "Database ready, running migrations..."
php artisan migrate --force

echo "Checking Passport setup..."

# Vérifier et générer les clés Passport si nécessaires
if [ ! -f "storage/oauth-private.key" ] || [ ! -f "storage/oauth-public.key" ]; then
  echo "Generating Passport keys..."
  php artisan passport:keys --force
else
  echo "Passport keys already exist"
fi

# Vérifier si un client password existe
CLIENT_COUNT=$(php artisan tinker --execute='echo \Laravel\Passport\Client::where("password_client", true)->count();' 2>/dev/null || echo "0")

if [ "$CLIENT_COUNT" -eq 0 ]; then
  echo "Creating Passport clients..."
  php artisan passport:install --force
else
  echo "Passport password client already exists"
fi

# Vérifier si l'utilisateur admin existe
ADMIN_EXISTS=$(php artisan tinker --execute='echo \App\Models\User::where("login", "admin")->count();' 2>/dev/null || echo "0")

if [ "$ADMIN_EXISTS" -eq 0 ]; then
  echo "Creating admin user..."
  php artisan tinker --execute='
    \App\Models\User::create([
      "login" => "admin",
      "password" => bcrypt("password"),
      "is_admin" => true,
      "nom" => "Admin",
      "prenom" => "System",
      "email" => "admin@linguerebank.sn"
    ]);
    echo "Admin user created successfully";
  '
else
  echo "Admin user already exists"
fi

echo "Setup complete! Starting Laravel..."
exec "$@"