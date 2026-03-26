<?php

namespace App\Http\Controllers;

use App\Services\BlockchainService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    protected BlockchainService $blockchain;

    public function __construct(BlockchainService $blockchain)
    {
        $this->blockchain = $blockchain;
    }

    // POST /transactions
    public function store(Request $request)
    {
        $request->validate([
            'persona_id'      => 'required|uuid',
            'institucion_id'  => 'required|uuid',
            'programa_id'     => 'required|uuid',
            'titulo_obtenido' => 'required|string',
            'fecha_fin'       => 'required|date',
        ]);

        $datos = $request->all();
        $transaccion = $this->blockchain->agregarTransaccion($datos);

        // Propagar a otros nodos
        $this->blockchain->propagarTransaccion($datos);

        Log::info("[API] POST /transactions - transacción creada: {$transaccion->id}");

        return response()->json([
            'mensaje'     => 'Transacción agregada y propagada',
            'transaccion' => $transaccion,
        ], 201);
    }

    // GET /transactions/pending
    public function pending()
    {
        $pendientes = $this->blockchain->obtenerTransaccionesPendientes();
        Log::info("[API] GET /transactions/pending - total: " . count($pendientes));

        return response()->json([
            'transacciones_pendientes' => $pendientes,
            'total'                    => count($pendientes),
        ]);
    }
}
