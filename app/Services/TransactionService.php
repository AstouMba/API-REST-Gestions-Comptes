<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Compte;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;

class TransactionService
{
    public function getAllTransactions()
    {
        return Transaction::all();
    }

    public function getTransactionById($id)
    {
        $transaction = Transaction::find($id);
        if (!$transaction) {
            throw new NotFoundException('Transaction introuvable');
        }
        return $transaction;
    }

    public function getTransactionsByCompte($numeroCompte)
    {
        $compte = Compte::byNumero($numeroCompte)->first();
        if (!$compte) {
            throw new NotFoundException('Compte introuvable');
        }
        return $compte->transactions;
    }

    public function createTransaction(array $data)
    {
        // Simuler l'utilisateur connecté
        // $data['user_id'] = 1; // ID fixe temporaire - removed as not in model
        $data['id'] = \Illuminate\Support\Str::uuid();
        return Transaction::create($data);
    }

    public function updateTransaction($id, array $data)
    {
        $transaction = Transaction::find($id);
        if (!$transaction) {
            throw new NotFoundException('Transaction introuvable');
        }
        $transaction->update($data);
        return $transaction;
    }

    public function deleteTransaction($id)
    {
        $transaction = Transaction::find($id);
        if (!$transaction) {
            throw new NotFoundException('Transaction introuvable');
        }
        $transaction->delete();
        return $transaction;
    }
}