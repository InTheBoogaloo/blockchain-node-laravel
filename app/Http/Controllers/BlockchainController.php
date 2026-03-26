<?php

namespace App\Http\Controllers;

use App\Services\BlockchainService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Info(
 *     title="Blockchain Node API - Laravel",
 *     version="1.0.0",
 *     description="API REST para nodo blockchain distribuido de grados académicos. Implementa Proof of Work, consenso distribuido y propagación entre nodos.",
 *     @OA\Contact(
 *         email="admin@blockchain-node.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8004",
 *     description="Nodo Laravel - Puerto 8004"
 * )
 * @OA\PathItem(path="/api")
 *
 * @OA\Tag(name="Blockchain", description="Cadena de bloques y minado")
 * @OA\Tag(name="Transacciones", description="Gestión de transacciones pendientes")
 * @OA\Tag(name="Nodos", description="Registro y sincronización de nodos")
 */
class BlockchainController extends Controller
{
    protected BlockchainService $blockchain;

    public function __construct(BlockchainService $blockchain)
    {
        $this->blockchain = $blockchain;
    }

    /**
     * @OA\Get(
     *     path="/api/chain",
     *     tags={"Blockchain"},
     *     summary="Obtener la cadena completa de bloques",
     *     description="Retorna todos los bloques de la cadena local ordenados cronológicamente.",
     *     @OA\Response(
     *         response=200,
     *         description="Cadena obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="chain", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="length", type="integer", example=2)
     *         )
     *     )
     * )
     */
    public function chain()
    {
        $cadena = $this->blockchain->obtenerCadena();
        Log::info("[API] GET /chain - bloques: " . count($cadena));

        return response()->json([
            'chain'  => $cadena,
            'length' => count($cadena),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/mine",
     *     tags={"Blockchain"},
     *     summary="Minar transacciones pendientes",
     *     description="Ejecuta el Proof of Work sobre las transacciones pendientes, genera nuevos bloques, los agrega a la cadena local y los propaga a los demás nodos.",
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="firmado_por", type="string", example="nodo-laravel-8004")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Bloques minados correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="mensaje", type="string"),
     *             @OA\Property(property="bloques", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="No hay transacciones pendientes",
     *         @OA\JsonContent(
     *             @OA\Property(property="mensaje", type="string", example="No hay transacciones pendientes para minar")
     *         )
     *     )
     * )
     */
    public function mine(Request $request)
    {
        $firmadoPor = $request->input('firmado_por', 'nodo-laravel-8004');
        $resultado  = $this->blockchain->minar($firmadoPor);

        if (isset($resultado['error'])) {
            return response()->json(['mensaje' => $resultado['error']], 400);
        }

        foreach ($resultado as $bloque) {
            $this->blockchain->propagarBloque($bloque);
        }

        Log::info("[API] POST /mine - bloques minados: " . count($resultado));

        return response()->json([
            'mensaje' => 'Bloques minados y propagados correctamente',
            'bloques' => $resultado,
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/blocks/receive",
     *     tags={"Blockchain"},
     *     summary="Recibir un bloque de otro nodo",
     *     description="Valida y agrega a la cadena local un bloque recibido de otro nodo de la red. Verifica hash, hash anterior y Proof of Work.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id","persona_id","institucion_id","titulo_obtenido","fecha_fin","hash_actual","hash_anterior","nonce"},
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="persona_id", type="string", format="uuid"),
     *             @OA\Property(property="institucion_id", type="string", format="uuid"),
     *             @OA\Property(property="titulo_obtenido", type="string"),
     *             @OA\Property(property="fecha_fin", type="string", format="date"),
     *             @OA\Property(property="hash_actual", type="string"),
     *             @OA\Property(property="hash_anterior", type="string"),
     *             @OA\Property(property="nonce", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Bloque aceptado",
     *         @OA\JsonContent(
     *             @OA\Property(property="mensaje", type="string", example="Bloque aceptado y agregado a la cadena")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bloque inválido",
     *         @OA\JsonContent(
     *             @OA\Property(property="mensaje", type="string", example="Bloque inválido")
     *         )
     *     )
     * )
     */
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
