<?php

namespace App\Http\Controllers;

use App\Services\BlockchainService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BlockchainController extends Controller
{
    protected BlockchainService $blockchain;

    public function __construct(BlockchainService $blockchain)
    {
        $this->blockchain = $blockchain;
    }

    // GET /chain
    public function chain()
    {
        $cadena = $this->blockchain->obtenerCadena();
        Log::info("[API] GET /chain - bloques: " . count($cadena));

        return response()->json([
            'chain'  => $cadena,
            'length' => count($cadena),
        ]);
    }

    // POST /mine
    public function mine(Request $request)
    {
        $firmadoPor = $request->input('firmado_por', 'nodo-laravel-8004');
        $resultado  = $this->blockchain->minar($firmadoPor);

        if (isset($resultado['error'])) {
            return response()->json(['mensaje' => $resultado['error']], 400);
        }

        // Propagar cada bloque minado
        foreach ($resultado as $bloque) {
            $this->blockchain->propagarBloque($bloque);
        }

        Log::info("[API] POST /mine - bloques minados: " . count($resultado));

        return response()->json([
            'mensaje' => 'Bloques minados y propagados correctamente',
            'bloques' => $resultado,
        ], 201);
    }

    // POST /blocks/receive
    public function receiveBlock(Request $request)
    {
        $bloque = $request->all();
        $cadena = $this->blockchain->obtenerCadena();
        $ultimoBloque = !empty($cadena) ? end($cadena) : null;

        if (!$this->blockchain->validarBloque($bloque, $ultimoBloque)) {
            Log::warning("[API] POST /blocks/receive - bloque inválido rechazado");
            return response()->json(['mensaje' => 'Bloque inválido'], 400);
        }

        \App\Models\Grado::create($bloque);
        Log::info("[API] POST /blocks/receive - bloque aceptado: {$bloque['id']}");

        return response()->json(['mensaje' => 'Bloque aceptado y agregado a la cadena'], 201);
    }
}
