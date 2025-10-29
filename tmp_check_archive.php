<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$archived = DB::connection('neon')
    ->table('comptes_archives')
    ->where('compte_id', '6ba6419d-e7b4-4d22-8982-34a687f64426')
    ->first();

if ($archived) {
    print_r($archived);
} else {
    echo "Compte archivé non trouvé dans Neon\n";
}