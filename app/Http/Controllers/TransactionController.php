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

    /**
     * @OA\Post(
     *     path="/api/transactions",
     *     tags={"Transacciones"},
     *     summary="Crear una nueva transacción",
     *     description="Recibe los datos de un grado académico, los guarda como transacción pendiente y los propaga a todos los nodos registrados en la red.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"persona_id","institucion_id","programa_id","titulo_obtenido","fecha_fin"},
     *             @OA\Property(property="persona_id", type="string", format="uuid", example="286cd43f-f789-4903-9ae6-de288bd81e25"),
     *             @OA\Property(property="institucion_id", type="string", format="uuid", example="8a488bb8-f8d8-48da-a88e-2abd852aea84"),
     *             @OA\Property(property="programa_id", type="string", format="uuid", example="6df0a016-a1d7-4ee4-ac92-9af454b9454f"),
     *             @OA\Property(property="titulo_obtenido", type="string", example="Ingeniero en Sistemas Computacionales"),
     *             @OA\Property(property="fecha_inicio", type="string", format="date", example="2019-08-01"),
     *             @OA\Property(property="fecha_fin", type="string", format="date", example="2024-06-15"),
     *             @OA\Property(property="numero_cedula", type="string", example="12345678"),
     *             @OA\Property(property="titulo_tesis", type="string", example="Implementación de redes neuronales"),
     *             @OA\Property(property="menciones", type="string", example="Cum Laude")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Transacción creada y propagada",
     *         @OA\JsonContent(
     *             @OA\Property(property="mensaje", type="string", example="Transacción agregada y propagada"),
     *             @OA\Property(property="transaccion", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos inválidos",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
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

        $this->blockchain->propagarTransaccion($datos);

        Log::info("[API] POST /transactions - transacción creada: {$transaccion->id}");

        return response()->json([
            'mensaje'     => 'Transacción agregada y propagada',
            'transaccion' => $transaccion,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/transactions/pending",
     *     tags={"Transacciones"},
     *     summary="Listar transacciones pendientes",
     *     description="Retorna todas las transacciones que aún no han sido incluidas en un bloque minado.",
     *     @OA\Response(
     *         response=200,
     *         description="Lista de transacciones pendientes",
     *         @OA\JsonContent(
     *             @OA\Property(property="transacciones_pendientes", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="total", type="integer", example=1)
     *         )
     *     )
     * )
     */
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
