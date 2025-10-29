<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Compte;
use App\Services\CompteBlockageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UnarchiveAccountsJobTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function debloquer_comptes_echus_restores_compte_and_transactions()
    {
        // Create a compte and soft-delete it to simulate an archived account
        $compte = Compte::factory()->create([
            'id' => '11111111-1111-1111-1111-111111111111',
            'type' => 'epargne',
            'statut' => 'bloque',
        ]);
        $compte->delete();

        $compteArchive = (object) [
            'compte_id' => $compte->id,
        ];

        $transaction = (object) [
            'transaction_id' => '22222222-2222-2222-2222-222222222222',
            'compte_id' => $compte->id,
            'type' => 'depot',
            'montant' => 1000,
            'date_transaction' => Carbon::now()->toDateTimeString(),
            'created_at' => Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString(),
        ];

        // Stub for the neon connection returned by DB::connection('neon')
        $stub = new class($compteArchive, $transaction) {
            private $compteArchive;
            private $transaction;

            public function __construct($compteArchive, $transaction)
            {
                $this->compteArchive = $compteArchive;
                $this->transaction = $transaction;
            }

            public function table($name)
            {
                $that = $this;
                return new class($name, $that) {
                    private $name;
                    private $that;

                    public function __construct($name, $that)
                    {
                        $this->name = $name;
                        $this->that = $that;
                    }

                    public function where(...$args)
                    {
                        return $this;
                    }

                    public function get()
                    {
                        if ($this->name === 'comptes_archives') {
                            return collect([$this->that->compteArchive]);
                        }

                        if ($this->name === 'transactions_archives') {
                            return collect([$this->that->transaction]);
                        }

                        return collect();
                    }

                    public function delete()
                    {
                        return 1;
                    }
                };
            }
        };

        DB::shouldReceive('connection')->with('neon')->andReturn($stub);

        $service = new CompteBlockageService();
        $service->debloquerComptesEchus();

        $this->assertEquals('actif', $compte->fresh()->statut);
        $this->assertDatabaseHas('transactions', ['id' => $transaction->transaction_id]);
    }
}
