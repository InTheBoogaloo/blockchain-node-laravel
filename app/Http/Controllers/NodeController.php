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

    /**
     * @OA\Post(
     *     path="/api/nodes/register",
     *     tags={"Nodos"},
     *     summary="Registrar un nodo en la red",
     *     description="Agrega la URL de otro nodo a la lista de nodos conocidos para habilitar la propagación y el consenso.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"url"},
     *             @OA\Property(property="url", type="string", example="http://localhost:8001")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Nodo registrado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="mensaje", type="string", example="Nodo registrado correctamente"),
     *             @OA\Property(property="nodo", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="URL inválida",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/nodes",
     *     tags={"Nodos"},
     *     summary="Listar nodos registrados",
     *     description="Retorna todos los nodos conocidos por este nodo en la red.",
     *     @OA\Response(
     *         response=200,
     *         description="Lista de nodos",
     *         @OA\JsonContent(
     *             @OA\Property(property="nodos", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="total", type="integer", example=3)
     *         )
     *     )
     * )
     */
    public function index()
    {
        $nodos = Nodo::all();
        Log::info("[API] GET /nodes - total: " . count($nodos));

        return response()->json([
            'nodos' => $nodos,
            'total' => count($nodos),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/nodes/resolve",
     *     tags={"Nodos"},
     *     summary="Resolver conflictos por consenso",
     *     description="Consulta la cadena de todos los nodos registrados y adopta la cadena válida más larga. Implementa el algoritmo de consenso distribuido.",
     *     @OA\Response(
     *         response=200,
     *         description="Consenso ejecutado",
     *         @OA\JsonContent(
     *             @OA\Property(property="mensaje", type="string", example="La cadena local ya es la más larga"),
     *             @OA\Property(property="cadena", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
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
