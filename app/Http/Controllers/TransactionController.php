<?php

namespace App\Http\Controllers;

use App\Services\TransactionService;
use App\Traits\ApiResponseTrait;
use App\Http\Resources\TransactionResource;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    use ApiResponseTrait;

    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function index(Request $request)
    {
        $transactions = $this->transactionService->getAllTransactions();
        return $this->successResponse(TransactionResource::collection($transactions), 'Transactions retrieved successfully');
    }

    public function indexByCompte(Request $request, $numero)
    {
        $transactions = $this->transactionService->getTransactionsByCompte($numero);
        return $this->successResponse(TransactionResource::collection($transactions), 'Transactions retrieved successfully');
    }

    public function show($id)
    {
        $transaction = $this->transactionService->getTransactionById($id);
        return $this->successResponse(new TransactionResource($transaction), 'Transaction retrieved successfully');
    }

    public function store(StoreTransactionRequest $request)
    {
        $transaction = $this->transactionService->createTransaction($request->validated());
        return $this->successResponse(new TransactionResource($transaction), 'Transaction created successfully', 201);
    }

    public function update(UpdateTransactionRequest $request, $id)
    {
        $transaction = $this->transactionService->updateTransaction($id, $request->validated());
        return $this->successResponse(new TransactionResource($transaction), 'Transaction updated successfully');
    }

    public function destroy($id)
    {
        $this->transactionService->deleteTransaction($id);
        return $this->successResponse(null, 'Transaction deleted successfully');
    }
}