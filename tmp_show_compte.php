<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$compte = App\Models\Compte::withoutGlobalScopes()->withTrashed()->find('20aca353-2068-449b-8b18-4c654556c511');
if (! $compte) {
    echo "Compte not found\n"; exit(1);
}
print_r($compte->toArray());
