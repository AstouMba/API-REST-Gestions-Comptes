<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$exists = DB::connection('neon')->getSchemaBuilder()->hasTable('comptes_archives');
if (! $exists) {
    echo "No comptes_archives table on neon\n";
    exit(0);
}
$cols = DB::connection('neon')->getSchemaBuilder()->getColumnListing('comptes_archives');
print_r($cols);
$count = DB::connection('neon')->table('comptes_archives')->count();
echo "count: $count\n";
