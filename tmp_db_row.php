<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
$row = DB::table('comptes')->where('id', '20aca353-2068-449b-8b18-4c654556c511')->first();
print_r($row);
