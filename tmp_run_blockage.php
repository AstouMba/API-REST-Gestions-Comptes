<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// ID du compte de test (changez si besoin)
$compteId = '20aca353-2068-449b-8b18-4c654556c511';

// Marquer le compte pour blocage immédiat (utiliser snake_case colonne ajoutée)
DB::table('comptes')->where('id', $compteId)->update([
    'motif_blocage' => 'Test archive immediate',
    'date_blocage' => Carbon::now(),
    'date_deblocage_prevue' => Carbon::now()->addMonths(6),
]);

echo "Marked compte $compteId for blocking (dateBlocage = now)\n";

// Lancer le service de blocage qui archive vers Neon
$svc = new App\Services\CompteBlockageService();
$svc->bloquerComptesEchus();

echo "bloquerComptesEchus() executed.\n";

// Vérifier archives sur Neon
$exists = DB::connection('neon')->table('comptes_archives')->where('compte_id', $compteId)->exists();
echo "Archived on neon (comptes_archives exists for compte): " . ($exists ? 'yes' : 'no') . "\n";

if ($exists) {
    $row = DB::connection('neon')->table('comptes_archives')->where('compte_id', $compteId)->first();
    print_r($row);
    $txCount = DB::connection('neon')->table('transactions_archives')->where('compte_id', $compteId)->count();
    echo "Transactions archived: $txCount\n";
}

// Show local compte status
$local = DB::table('comptes')->where('id', $compteId)->first();
echo "Local compte row:\n"; print_r($local);

echo "Done.\n";
