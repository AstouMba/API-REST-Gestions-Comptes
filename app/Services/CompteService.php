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
            // Try to find existing client by id, telephone, email or nci
            $client = null;
            if (!empty($data['client_id'])) {
                $client = Client::find($data['client_id']);
            }
            if (!$client && !empty($data['telephone'])) {
                $client = Client::where('telephone', $data['telephone'])->first();
            }
            if (!$client && !empty($data['email'])) {
                $client = Client::where('email', $data['email'])->first();
            }
            if (!$client && !empty($data['nci'])) {
                $client = Client::where('nci', $data['nci'])->first();
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
                    'titulaire' => $data['titulaire'] ?? ($data['client']['titulaire'] ?? null),
                    'email' => $data['email'] ?? ($data['client']['email'] ?? null),
                    'adresse' => $data['adresse'] ?? null,
                    'telephone' => $data['telephone'] ?? null,
                    'nci' => $data['nci'] ?? null,
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

            // Create the compte
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

            return $compte;
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
