<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $pdo = DB::connection('neon')->getPdo();
    echo "Connexion à Neon établie avec succès!\n";
    
    $tables = DB::connection('neon')->select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
    echo "\nTables disponibles dans la base Neon:\n";
    foreach ($tables as $table) {
        echo "- " . $table->tablename . "\n";
    }
} catch (Exception $e) {
    echo "Erreur de connexion à Neon: " . $e->getMessage() . "\n";
    echo "Code d'erreur: " . $e->getCode() . "\n";
}