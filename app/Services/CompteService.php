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
    public function listComptes($user = null, $filters = [])
    {
        $query = Compte::query();

        // Apply user scope (for admin / client) if available
        if (method_exists(Compte::class, 'scopeForUser')) {
            $query = $query->forUser($user);
        }

        // Apply search scope if provided
        if (!empty($filters['search']) && method_exists(Compte::class, 'scopeSearch')) {
            $query = $query->search($filters['search']);
        }

        // Apply type scope if provided
        if (!empty($filters['type']) && method_exists(Compte::class, 'scopeType')) {
            $query = $query->type($filters['type']);
        }

        // By default only return active accounts (and those with non-expired blocking dates)
        if (method_exists(Compte::class, 'scopeActifs')) {
            $query = $query->actifs();
        }

        // Sorting
        $allowedSorts = ['created_at', 'numero', 'titulaire', 'solde'];
        $sort = $filters['sort'] ?? 'created_at';
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
        }
        $order = strtolower($filters['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query = $query->orderBy($sort, $order);

        // Pagination
        $limit = isset($filters['limit']) && is_numeric($filters['limit']) ? (int) $filters['limit'] : 10;

        return $query->paginate($limit);
    }

    public function createCompte(array $data)
    {
        // Create client (if needed), user (if needed) and compte within a transaction.
        return DB::transaction(function () use ($data) {
            // Extract client data
            $clientData = $data['client'] ?? [];
            
            // Try to find existing client by id, telephone, email or nci
            $client = null;
            if (!empty($data['client_id'])) {
                $client = Client::find($data['client_id']);
            }
            if (!$client && !empty($clientData['telephone'])) {
                $client = Client::where('telephone', $clientData['telephone'])->first();
            }
            if (!$client && !empty($clientData['email'])) {
                $client = Client::where('email', $clientData['email'])->first();
            }
            if (!$client && !empty($clientData['nci'])) {
                $client = Client::where('nci', $clientData['nci'])->first();
            }

            $password = null;
            $user = null;

            // If client does not exist, create user + client
            if (! $client) {
                $password = Str::random(10);
                $login = $data['telephone'] ?? $data['email'] ?? 'user' . Str::random(6);

                $user = User::create([
                    'id' => (string) Str::uuid(),
                    'login' => $login,
                    'password' => $password,
                    'code' => null,
                    'is_admin' => false,
                ]);

                $client = Client::create([
                    'id' => (string) Str::uuid(),
                    'utilisateur_id' => $user->id,
                    'titulaire' => $clientData['titulaire'] ?? null,
                    'email' => $clientData['email'] ?? null,
                    'adresse' => $clientData['adresse'] ?? null,
                    'telephone' => $clientData['telephone'] ?? null,
                    'nci' => $clientData['nci'] ?? null,
                ]);
            } else {
                // Client exists: ensure user exists
                $user = $client->utilisateur;
                if (! $user) {
                    $password = Str::random(10);
                    $login = $client->telephone ?? $client->email ?? 'user' . Str::random(6);
                    $user = User::create([
                        'id' => (string) Str::uuid(),
                        'login' => $login,
                        'password' => $password,
                        'code' => null,
                        'is_admin' => false,
                    ]);
                    $client->utilisateur_id = $user->id;
                    $client->save();
                }
            }

            // Validate using custom rules
            $clientData = $data['client'] ?? [];
            
            $nciRule = new NciRule();
            if (!empty($clientData['nci'])) {
                $errorMessage = null;
                $nciRule->validate('nci', $clientData['nci'], function($message) use (&$errorMessage) {
                    $errorMessage = $message;
                });
                if ($errorMessage !== null) {
                    throw new \InvalidArgumentException($errorMessage);
                }
            }

            $telephoneRule = new TelephoneSenegalRule();
            if (!empty($clientData['telephone'])) {
                $errorMessage = null;
                $telephoneRule->validate('telephone', $clientData['telephone'], function($message) use (&$errorMessage) {
                    $errorMessage = $message;
                });
                if ($errorMessage !== null) {
                    throw new \InvalidArgumentException($errorMessage);
                }
            } 

            // Create the compte (numero will be generated by observer)
            $compte = Compte::create([
                'id' => (string) Str::uuid(),
                'client_id' => $client->id,
                'type' => $data['type'] ?? 'cheque',
                'statut' => $data['statut'] ?? 'actif',
                'devise' => $data['devise'] ?? 'FCFA',
            ]);

            // Create initial deposit transaction if soldeInitial is provided
            if (isset($data['soldeInitial']) && is_numeric($data['soldeInitial'])) {
                $compte->depots()->create([
                    'id' => (string) Str::uuid(),
                    'montant' => $data['soldeInitial'],
                    'type' => 'depot',
                    'description' => 'Solde initial',
                ]);
            }

            // Generate a verification code and store on user
            $code = (string) random_int(100000, 999999);
            if ($user) {
                $user->code = $code;
                $user->save();
            }

            // Dispatch event to send notifications (mail + sms)
            try {
                event(new CompteCreated($compte, $client, $password, $code));
            } catch (\Exception $e) {
                Log::error('Failed to dispatch CompteCreated event: ' . $e->getMessage());
            }

            // Format response according to API specification
            return [
                'success' => true,
                'message' => 'Compte créé avec succès',
                'data' => [
                    'id' => $compte->id,
                    'numeroCompte' => $compte->numero,
                    'titulaire' => $client->titulaire,
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

    public function getCompteById(string $id): ?Compte
    {
        // Récupération directe du compte sans vérification d'autorisation pour le moment
        return Compte::withoutGlobalScopes()->find($id);
    }

    /**
     * Retourne un payload uniforme pour un compte, qu'il soit actif (local) ou archivé (neon).
     * Renvoie null si le compte n'existe ni localement (actif/trashed) ni dans les archives.
     *
     * @param string $compteId
     * @return array|null
     */
    public function getComptePayload(string $compteId): ?array
    {
        // Try active local account (global scopes apply)
        $localActive = Compte::with(['client', 'depots', 'retraits'])->find($compteId);
        if ($localActive) {
            // Use resource to build standard payload
            $payload = (new CompteResource($localActive))->toArray(request());
            return $payload;
        }

        // Try neon archive
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
            $archivedBy = $user->name ?? $user->id ?? null;
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

        // Support both snake_case and camelCase DB column names (some deployments use motifBlocage/dateBlocage)
        $updatePayload = [
            // snake_case (preferred in codebase)
            'motif_blocage' => $data['motif'],
            'date_blocage' => $dateBlocage,
            'date_deblocage_prevue' => $dateBlocage->copy()->addMonths(6),
            // camelCase (some migrations used camelCase column names)
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

        \DB::beginTransaction();
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

            \DB::commit();
            return $compte->refresh();
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }
}
