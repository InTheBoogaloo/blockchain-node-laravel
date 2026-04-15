<?php

use App\Http\Controllers\BlockchainController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\NodeController;
use Illuminate\Support\Facades\Route;

// ─── Blockchain ───────────────────────────────────────────────
Route::get('/chain', [BlockchainController::class, 'chain']);
Route::post('/mine', [BlockchainController::class, 'mine']);
Route::post('/blocks/receive', [BlockchainController::class, 'receiveBlock']);

// ─── Transacciones ────────────────────────────────────────────
Route::post('/transactions', [TransactionController::class, 'store']);
Route::get('/transactions/pending', [TransactionController::class, 'pending']);

// ─── Nodos ────────────────────────────────────────────────────
Route::post('/nodes/register', [NodeController::class, 'register']);
Route::get('/nodes', [NodeController::class, 'index']);
Route::get('/nodes/resolve', [NodeController::class, 'resolve']);

// ─── Génesis ──────────────────────────────────────────────────
Route::post('/genesis', [BlockchainController::class, 'genesis']);

// ─── Health ───────────────────────────────────────────────────
Route::get('/health', [BlockchainController::class, 'health']);
Route::get('/health', function () { return response()->json(['status' => 'ok']); });
