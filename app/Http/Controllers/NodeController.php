<?php

namespace App\Http\Controllers;

use App\Models\Nodo;
use App\Services\BlockchainService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class NodeController extends Controller
{
    protected BlockchainService $blockchain;

    public function __construct(BlockchainService $blockchain)
    {
        $this->blockchain = $blockchain;
    }

    // POST /nodes/register
    public function register(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        $url = rtrim($request->input('url'), '/');

        $nodo = Nodo::firstOrCreate(
            ['url' => $url],
            ['id' => Str::uuid(), 'creado_en' => now()]
        );

        Log::info("[API] POST /nodes/register - nodo registrado: {$url}");

        return response()->json([
            'mensaje' => 'Nodo registrado correctamente',
            'nodo'    => $nodo,
        ], 201);
    }

    // GET /nodes
    public function index()
    {
        $nodos = Nodo::all();
        Log::info("[API] GET /nodes - total: " . count($nodos));

        return response()->json([
            'nodos' => $nodos,
            'total' => count($nodos),
        ]);
    }

    // GET /nodes/resolve
    public function resolve()
    {
        $resultado = $this->blockchain->resolverConflictos();
        Log::info("[API] GET /nodes/resolve - reemplazada: " . ($resultado['reemplazada'] ? 'sí' : 'no'));

        return response()->json([
            'mensaje' => $resultado['reemplazada']
                ? 'Cadena reemplazada por una más larga y válida'
                : 'La cadena local ya es la más larga',
            'cadena'  => $resultado['cadena'],
        ]);
    }
}
