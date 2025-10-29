<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Users: " . App\Models\User::count() . "\n";
echo "Clients: " . App\Models\Client::count() . "\n";
echo "Comptes: " . App\Models\Compte::count() . "\n";
echo "Transactions: " . App\Models\Transaction::count() . "\n";

$neonConfigured = (bool) config('database.connections.neon');
if ($neonConfigured) {
    echo "Neon configured\n";
    $exists = Schema::connection('neon')->hasTable('comptes_archives');
    echo "Neon comptes_archives table exists: " . ($exists ? 'yes' : 'no') . "\n";
    if ($exists) {
        echo "Neon comptes_archives count: " . DB::connection('neon')->table('comptes_archives')->count() . "\n";
    }
} else {
    echo "Neon not configured\n";
}
