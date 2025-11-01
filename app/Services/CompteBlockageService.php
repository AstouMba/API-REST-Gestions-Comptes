<?php

namespace App\Services;

use App\Models\Compte;
use App\Models\CompteArchive;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompteBlockageService
{
    /**
     * Planifier le blocage d'un compte épargne
     */
    public function planifierBlocage(Compte $compte, string $motif, int $duree, string $unite, ?string $dateDebut = null): void
    {
        if ($compte->type !== 'epargne') {
            throw new \InvalidArgumentException('Seuls les comptes épargne peuvent être bloqués');
        }

        // date_debut is the planned start of the blocking
        $dateBase = $dateDebut ? Carbon::parse($dateDebut) : Carbon::now();

        // The planned date of block is the provided dateBase (date_debut)
        $dateBlocage = $dateBase->copy();

        // The expected unblock date is date_debut + duration according to the unit
        $dateDeblocagePrevue = match ($unite) {
            'jours' => $dateBase->copy()->addDays($duree),
            'mois' => $dateBase->copy()->addMonths($duree),
            'annees' => $dateBase->copy()->addYears($duree),
            default => throw new \InvalidArgumentException('Unité de temps invalide. Utilisez: jours, mois ou annees')
        };

        $compte->update([
            'motif_blocage' => $motif,
            'date_blocage' => $dateBlocage,
            'date_deblocage_prevue' => $dateDeblocagePrevue,
        ]);

        // Ensure the passed model instance contains the latest attributes
        $compte->refresh();
    }

    /**
     * Bloquer les comptes dont la date de blocage est échue et les archiver
     */
    public function bloquerComptesEchus(): void
    {
        Compte::query()
            ->where('type', 'epargne')
            ->where('statut', 'actif')
            ->whereNotNull('date_blocage')
            ->where('date_blocage', '<=', Carbon::now())
            ->each(function (Compte $compte) {
                // Archiver le compte
                $this->archiverCompte($compte);
                
                // Marquer comme bloqué et soft delete
                $compte->update(['statut' => 'bloque']);
                $compte->delete();
            });
    }
    

    /**
     * Archiver un compte dans la base Neon
     */
    private function archiverCompte(Compte $compte): void
    {
        // Use the Eloquent model bound to the 'neon' connection to ensure consistent inserts
        try {
            CompteArchive::create([
                'id' => $compte->id,
                'numero' => $compte->numero,
                'titulaire' => optional($compte->client)->titulaire ?? null,
                'type' => $compte->type,
                'solde' => $compte->solde,
                'devise' => $compte->devise,
                'statut' => $compte->statut,
                // blocage fields
                'motif_blocage' => $compte->motif_blocage,
                'date_blocage' => $compte->date_blocage,
                'date_deblocage_prevue' => $compte->date_deblocage_prevue,
                // dates de creation/fermeture utilisées par la migration/model
                'dateCreation' => $compte->created_at,
                'dateFermeture' => null,
                'metadata' => [
                    'archivedBy' => 'system',
                    'archivedAt' => Carbon::now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            // If neon is unavailable or insert fails, log the error but continue
            Log::error('Failed to archive compte to neon via CompteArchive model: ' . $e->getMessage(), ['compte' => $compte->id]);
        }

        // Archiver aussi les transactions associées (table transaction_archives attendues)
        $compte->transactions->each(function ($transaction) {
            try {
                DB::connection('neon')->table('transactions_archives')->insert([
                    'transaction_id' => $transaction->id,
                    'compte_id' => $transaction->compte_id,
                    'type' => $transaction->type,
                    'montant' => $transaction->montant,
                    'date_transaction' => $transaction->date_transaction,
                    'created_at' => $transaction->created_at,
                    'updated_at' => $transaction->updated_at,
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to archive transaction to neon: ' . $e->getMessage(), ['transaction' => $transaction->id]);
            }
        });
    }

    /**
     * Débloquer les comptes dont la date de déblocage est échue
     */
    public function debloquerComptesEchus(): void
    {
        // Check if neon connection is configured
        $neonConfigured = config('database.connections.neon') !== null;
        if (!$neonConfigured) {
            // If neon is not configured, just update comptes directly based on date_deblocage_prevue
            Compte::where('statut', 'bloque')
                ->whereNotNull('date_deblocage_prevue')
                ->where('date_deblocage_prevue', '<=', Carbon::now())
                ->update([
                    'statut' => 'actif',
                    'motif_blocage' => null,
                    'date_blocage' => null,
                    'date_deblocage_prevue' => null
                ]);
            return;
        }

        // Rechercher dans la base Neon les comptes à débloquer
        $comptesADebloquer = DB::connection('neon')
            ->table('comptes_archives')
            ->where('date_deblocage_prevue', '<=', Carbon::now())
            ->get();

        foreach ($comptesADebloquer as $compteArchive) {
            // Restaurer le compte (annuler le soft delete)
            Compte::withTrashed()
                ->where('id', $compteArchive->compte_id)
                ->restore();

            // Mettre à jour le statut et les dates
            Compte::find($compteArchive->compte_id)->update([
                'statut' => 'actif',
                'motif_blocage' => null,
                'date_blocage' => null,
                'date_deblocage_prevue' => null
            ]);

            // Restaurer les transactions depuis Neon
            $transactions = DB::connection('neon')
                ->table('transactions_archives')
                ->where('compte_id', $compteArchive->compte_id)
                ->get();

            foreach ($transactions as $transaction) {
                DB::table('transactions')->insert([
                    'id' => $transaction->transaction_id,
                    'compte_id' => $transaction->compte_id,
                    'type' => $transaction->type,
                    'montant' => $transaction->montant,
                    'date_transaction' => $transaction->date_transaction,
                    'created_at' => $transaction->created_at,
                    'updated_at' => $transaction->updated_at,
                ]);
            }

            // Supprimer les archives de Neon
            DB::connection('neon')
                ->table('transactions_archives')
                ->where('compte_id', $compteArchive->compte_id)
                ->delete();

            DB::connection('neon')
                ->table('comptes_archives')
                ->where('compte_id', $compteArchive->compte_id)
                ->delete();
        }
    }

    /**
     * Débloquer un compte spécifique
     */
    public function debloquerCompte(Compte $compte): void
    {
        if ($compte->type !== 'epargne') {
            throw new \InvalidArgumentException('Seuls les comptes épargne peuvent être débloqués');
        }

        if ($compte->statut !== 'bloque') {
            throw new \InvalidArgumentException('Ce compte n\'est pas bloqué');
        }

        // Restaurer le compte si soft deleted
        if ($compte->trashed()) {
            $compte->restore();
        }

        $compte->update([
            'statut' => 'actif',
            'motif_blocage' => null,
            'date_blocage' => null,
            'date_deblocage_prevue' => null,
        ]);

        // Restaurer les transactions depuis Neon si nécessaire
        $transactions = DB::connection('neon')
            ->table('transactions_archives')
            ->where('compte_id', $compte->id)
            ->get();

        foreach ($transactions as $transaction) {
            DB::table('transactions')->insert([
                'id' => $transaction->transaction_id,
                'compte_id' => $transaction->compte_id,
                'type' => $transaction->type,
                'montant' => $transaction->montant,
                'date_transaction' => $transaction->date_transaction,
                'created_at' => $transaction->created_at,
                'updated_at' => $transaction->updated_at,
            ]);
        }

        // Supprimer les archives de Neon
        DB::connection('neon')
            ->table('transactions_archives')
            ->where('compte_id', $compte->id)
            ->delete();
        DB::connection('neon')
            ->table('comptes_archives')
            ->where('compte_id', $compte->id)
            ->delete();
    }

    /**
     * Vérifier si un compte peut être programmé pour blocage
     */
    public function peutEtreProgrammePourBlocage(Compte $compte): bool
    {
        return $compte->type === 'epargne' && 
               $compte->statut === 'actif' &&
               $compte->date_blocage === null;
    }

    /**
     * Vérifier si un compte peut être débloqué
     */
    public function peutEtreDebloque(Compte $compte): bool
    {
        return $compte->type === 'epargne' && 
               $compte->statut === 'bloque';
    }
}