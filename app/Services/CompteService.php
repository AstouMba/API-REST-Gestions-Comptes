<?php

namespace App\Services;

use App\Events\CompteCreated;
use App\Models\Compte;
use App\Models\CompteArchive;
use App\Models\Client;
use App\Models\User;
use App\Http\Resources\CompteResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Rules\NciRule;
use App\Rules\TelephoneSenegalRule;

class CompteService
{
    /**
     * Liste les comptes selon le rôle de l'utilisateur
     * - Admin : voit tous les comptes
     * - Client : voit uniquement ses propres comptes
     * 
     * @param User|null $user
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    use \App\Traits\PaginationTrait;

    public function listComptes($user = null, $filters = [])
    {
        $query = Compte::query();

        // Permissions: filter by user if not admin
        if (method_exists(Compte::class, 'scopeForUser')) {
            $query = $query->forUser($user);
        }

        // Scopes for filters
        if (!empty($filters['type']) && method_exists(Compte::class, 'scopeType')) {
            $query = $query->type($filters['type']);
        }
        if (!empty($filters['statut']) && method_exists(Compte::class, 'scopeStatut')) {
            $query = $query->statut($filters['statut']);
        }
        if (!empty($filters['search']) && method_exists(Compte::class, 'scopeSearch')) {
            $query = $query->search($filters['search']);
        }
        if (!empty($filters['client_id']) && $user && $user->is_admin) {
            $query = $query->where('client_id', $filters['client_id']);
        }
        if (!empty($filters['devise'])) {
            $query = $query->where('devise', $filters['devise']);
        }

        // By default only return active accounts (and those with non-expired blocking dates)
        if (method_exists(Compte::class, 'scopeActifs')) {
            $query = $query->actifs();
        }

        // Sorting
        $allowedSorts = ['created_at', 'numero', 'titulaire', 'solde', 'type', 'statut'];
        $sort = $filters['sort'] ?? 'created_at';
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
        }
        $order = strtolower($filters['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if ($sort === 'titulaire') {
            $query = $query->join('clients', 'comptes.client_id', '=', 'clients.id')
                ->select('comptes.*')
                ->orderBy('clients.titulaire', $order);
        } else {
            $query = $query->orderBy($sort, $order);
        }

        $limit = isset($filters['limit']) && is_numeric($filters['limit']) ? (int) $filters['limit'] : 10;

        $query = $query->with('client');

        return $query->paginate($limit);
    }

    public function createCompte(array $data)
    {
        return DB::transaction(function () use ($data) {
            $compte = new Compte();
            $compte->id = (string) Str::uuid();
            $compte->client_data = $data['client'] ?? [];
            $compte->soldeInitial = $data['soldeInitial'] ?? null;
            $compte->type = $data['type'] ?? 'cheque';
            $compte->statut = $data['statut'] ?? 'actif';
            $compte->devise = $data['devise'] ?? 'FCFA';
            if (!empty($data['client_id'])) {
                $compte->client_id = $data['client_id'];
            }
            $compte->save();

            // The observer has handled the business logic

            return [
                'success' => true,
                'message' => 'Compte créé avec succès',
                'data' => [
                    'id' => $compte->id,
                    'numeroCompte' => $compte->numero,
                    'titulaire' => $compte->client->titulaire,
                    'type' => $compte->type,
                    'solde' => $data['soldeInitial'] ?? 0,
                    'devise' => $compte->devise,
                    'dateCreation' => $compte->created_at->toIso8601String(),
                    'statut' => $compte->statut,
                    'metadata' => [
                        'derniereModification' => $compte->updated_at->toIso8601String(),
                        'version' => 1
                    ]
                ]
            ];
        });
    }

    /**
     * 
     * @param string $id
     * @param User|null $user
     * @return Compte|null
     */
    public function getCompteById(string $id, User $user = null): ?Compte
    {
        $compte = Compte::with('client')->find($id);
        
        if (!$compte) {
            return null;
        }

        // Si un utilisateur est fourni et qu'il n'est pas admin
        if ($user && !$user->is_admin) {
            $client = $user->client;
            
            // Vérifier que le compte appartient bien au client
            if (!$client || $compte->client_id !== $client->id) {
                return null; // Accès refusé
            }
        }

        return $compte;
    }

    /**
     * Retourne un payload uniforme pour un compte, qu'il soit actif (local) ou archivé (neon).
     * Renvoie null si le compte n'existe ni localement (actif/trashed) ni dans les archives.
     *
     * @param string $compteId
     * @param User|null $user Pour vérifier les permissions
     * @return array|null
     */
    public function getComptePayload(string $compteId, User $user = null): ?array
    {
        // Try active local account (global scopes apply)
        $localActive = Compte::with(['client', 'depots', 'retraits'])->find($compteId);
        
        if ($localActive) {
            if ($user && !$user->is_admin) {
                $client = $user->client;
                if (!$client || $localActive->client_id !== $client->id) {
                    return null; // Accès refusé
                }
            }
            
            // Use resource to build standard payload
            $payload = (new CompteResource($localActive))->toArray(request());
            return $payload;
        }

        // Try neon archive (uniquement pour les admins)
        if (!$user || !$user->is_admin) {
            return null; // Les clients ne peuvent pas voir les archives
        }

        try {
            $archive = CompteArchive::find($compteId);
        } catch (\Exception $e) {
            Log::warning('Neon archive lookup failed for compte '.$compteId.': '.$e->getMessage());
            $archive = null;
        }

        if ($archive) {
            // normalize metadata
            $meta = $archive->metadata ?? null;
            if (is_string($meta)) {
                $decoded = json_decode($meta, true);
                $meta = $decoded === null ? null : $decoded;
            } elseif (!is_array($meta)) {
                $meta = null;
            }

            $payload = [
                'id' => $archive->id,
                'numeroCompte' => $archive->numero ?? $archive->numeroCompte ?? null,
                'titulaire' => $archive->titulaire ?? null,
                'type' => $archive->type ?? null,
                'solde' => $archive->solde ?? 0,
                'devise' => $archive->devise ?? 'FCFA',
                'dateCreation' => isset($archive->datecreation) ? (Carbon::parse($archive->datecreation)->toISOString()) : (isset($archive->created_at) ? (string)$archive->created_at : null),
                'statut' => $archive->statut ?? 'ferme',
                'motifBlocage' => $archive->motifBlocage ?? null,
                'metadata' => $meta,
            ];

            return $payload;
        }

        // Final: check if exists locally (trashed/closed)
        $localAny = Compte::withoutGlobalScopes()->withTrashed()->with('client')->find($compteId);
        if ($localAny) {
            // present locally but not active and not archived -> return null to let caller handle
            return null;
        }

        return null;
    }

    /**
     * Soft-delete local compte and archive it to Neon.
     * Returns the response payload to return to client.
     *
     * @param string $compteId
     * @param Request|null $request
     * @return array
     * @throws \Exception
     */
    public function deleteCompte(string $compteId, ?Request $request = null): array
    {
        $compte = Compte::withoutGlobalScopes()->with('client')->find($compteId);
        if (! $compte) {
            throw new \Exception('Compte non trouvé');
        }

        // Seuls les comptes actifs peuvent être supprimés
        if ($compte->statut !== 'actif') {
            throw new \InvalidArgumentException('Seuls les comptes actifs peuvent être supprimés');
        }

        $closureTime = Carbon::now();

        DB::beginTransaction();
        try {
            // Update status
            $compte->statut = 'ferme';
            if (in_array('dateFermeture', $compte->getFillable()) || array_key_exists('dateFermeture', $compte->getAttributes())) {
                $compte->dateFermeture = $closureTime;
            }
            $compte->save();

            // Prepare archive data
            $user = $request ? $request->user() : null;
            $archivedBy = $user->login ?? $user->id ?? null;
            if (! $archivedBy && $request) {
                $archivedBy = $request->header('X-User-Name') ?? $request->header('X-User-Id') ?? 'system';
            }

            $archiveData = [
                'id' => $compte->id,
                'numero' => $compte->numero,
                'titulaire' => optional($compte->client)->titulaire ?? null,
                'type' => $compte->type ?? null,
                'solde' => $compte->solde ?? 0,
                'devise' => $compte->devise ?? 'FCFA',
                'statut' => 'ferme',
                'datecreation' => $compte->created_at ?? null,
                'datefermeture' => $closureTime,
                'metadata' => json_encode([
                    'archivedBy' => $archivedBy ?? 'system',
                    'archivedAt' => $closureTime->toIso8601String(),
                    'ip' => $request ? $request->ip() : null,
                ]),
            ];

            // Try to create archive on neon (failures are logged but do not block deletion)
            try {
                CompteArchive::create($archiveData);
                Log::info('Compte archived to neon', ['compte' => $compte->id, 'by' => $archivedBy]);
            } catch (\Exception $e) {
                Log::error('Failed to archive compte '.$compteId.' to neon: '.$e->getMessage());
            }

            // Soft delete local
            $compte->delete();

            DB::commit();

            return [
                'id' => $compte->id,
                'numero' => $compte->numero,
                'statut' => 'ferme',
                'dateFermeture' => $closureTime->toIso8601String(),
                'archive' => true,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error while deleting compte '.$compteId.': '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Planifier le blocage d'un compte
     */
    public function planifierBlocage(string $compteId, array $data): array
    {
        $compte = Compte::findOrFail($compteId);
        
        if ($compte->type !== 'epargne' || $compte->statut !== 'actif' || $compte->date_blocage !== null) {
            throw new \InvalidArgumentException(
                $compte->type !== 'epargne' 
                ? 'Seuls les comptes épargne peuvent être bloqués'
                : 'Ce compte ne peut pas être programmé pour blocage'
            );
        }

        $dateBlocage = match($data['unite']) {
            'jours' => Carbon::now()->addDays($data['duree']),
            'mois' => Carbon::now()->addMonths($data['duree']),
            'annees' => Carbon::now()->addYears($data['duree']),
            default => throw new \InvalidArgumentException('Unité de temps invalide. Utilisez: jours, mois ou annees')
        };

        // Support both snake_case and camelCase DB column names
        $updatePayload = [
            'motif_blocage' => $data['motif'],
            'date_blocage' => $dateBlocage,
            'date_deblocage_prevue' => $dateBlocage->copy()->addMonths(6),
            'motifBlocage' => $data['motif'],
            'dateBlocage' => $dateBlocage,
            'dateDeblocagePrevue' => $dateBlocage->copy()->addMonths(6),
        ];

        $compte->update($updatePayload);

        return [
            'id' => $compte->id,
            'statut' => $compte->statut,
            'motifBlocage' => $compte->motif_blocage,
            'dateBlocagePrevue' => $compte->date_blocage,
            'dateDeblocagePrevue' => $compte->date_deblocage_prevue,
            'note' => 'Le compte sera bloqué à la date prévue et archivé dans Neon. Il sera automatiquement restauré et débloqué à la date de déblocage.'
        ];
    }

    /**
     * Débloquer un compte
     */
    public function debloquerCompte(string $compteId): array
    {
        $compte = Compte::findOrFail($compteId);

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

        // Clear both naming styles to ensure field reset regardless of column naming
        $compte->update([
            'statut' => 'actif',
            'motif_blocage' => null,
            'date_blocage' => null,
            'date_deblocage_prevue' => null,
            'motifBlocage' => null,
            'dateBlocage' => null,
            'dateDeblocagePrevue' => null,
        ]);

        // Restaurer les transactions depuis Neon si nécessaire
        try {
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
        } catch (\Exception $e) {
            Log::error('Error restoring transactions from Neon: ' . $e->getMessage());
        }

        return [
            'id' => $compte->id,
            'statut' => $compte->statut,
            'dateDeblocage' => Carbon::now()
        ];
    }

    public function updateCompte(string $compteId, array $data, $user = null)
    {
        $compte = Compte::with('client.utilisateur')->find($compteId);
        if (!$compte) {
            throw new \Exception("Compte non trouvé");
        }

        DB::beginTransaction();
        try {
            // Update titulaire if provided (stored on client)
            if (array_key_exists('titulaire', $data)) {
                $compte->client->titulaire = $data['titulaire'];
                $compte->client->save();
            }

            // Update client informations
            if (!empty($data['informationsClient']) && is_array($data['informationsClient'])) {
                $client = $compte->client;
                $info = $data['informationsClient'];

                if (array_key_exists('telephone', $info) && $info['telephone'] !== null && $info['telephone'] !== '') {
                    $client->telephone = $info['telephone'];
                }
                if (array_key_exists('email', $info) && $info['email'] !== null && $info['email'] !== '') {
                    $client->email = $info['email'];
                }
                if (array_key_exists('nci', $info) && $info['nci'] !== null && $info['nci'] !== '') {
                    $client->nci = $info['nci'];
                }

                $client->save();

                // password on utilisateur (User model)
                if (array_key_exists('password', $info) && $info['password'] !== null && $info['password'] !== '') {
                    $userModel = $client->utilisateur;
                    if ($userModel) {
                        $userModel->password = $info['password'];
                        $userModel->save();
                    }
                }
            }

            DB::commit();
            return $compte->refresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}