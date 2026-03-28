<?php

namespace App\Http\Controllers;

use App\Services\BlockchainService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Info(
 *     title="Blockchain Node API - Laravel",
 *     version="1.0.0",
 *     description="API REST para nodo blockchain distribuido de grados académicos.",
 *     @OA\Contact(email="admin@blockchain-node.com")
 * )
 * @OA\Server(url="http://localhost:8004", description="Nodo Laravel - Puerto 8004")
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
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="firmado_por", type="string", example="nodo-laravel-8004")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Bloques minados correctamente"),
     *     @OA\Response(response=400, description="No hay transacciones pendientes")
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
     *     @OA\Response(response=201, description="Bloque aceptado"),
     *     @OA\Response(response=400, description="Bloque inválido")
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

    /**
     * @OA\Post(
     *     path="/api/genesis",
     *     tags={"Blockchain"},
     *     summary="Crear el bloque génesis",
     *     @OA\Response(response=201, description="Bloque génesis creado"),
     *     @OA\Response(response=400, description="La cadena ya tiene bloques")
     * )
     */
    public function genesis()
    {
        if (\App\Models\Grado::count() > 0) {
            return response()->json(['mensaje' => 'La cadena ya tiene bloques'], 400);
        }

        // IMPORTANTE: para el hash, persona_id e institucion_id se tratan como
        // string vacío "" porque en génesis son null — igual que hacen los demás nodos.
        $personaId      = '';
        $institucionId  = '';
        $tituloObtenido = 'GENESIS';
        $fechaFin       = '2000-01-01';
        $hashAnterior   = '';
        $nonce          = 0;

        do {
            $datos = $personaId . $institucionId . $tituloObtenido . $fechaFin . $hashAnterior . $nonce;
            $hash  = hash('sha256', $datos);
            $nonce++;
        } while (!str_starts_with($hash, '000'));

        $bloque = \App\Models\Grado::create([
            'id'              => \Illuminate\Support\Str::uuid(),
            'persona_id'      => null,
            'institucion_id'  => null,
            'programa_id'     => null,
            'titulo_obtenido' => 'GENESIS',
            'fecha_fin'       => '2000-01-01',
            'hash_actual'     => $hash,
            'hash_anterior'   => null,
            'nonce'           => $nonce - 1,
            'firmado_por'     => 'sistema',
            'creado_en'       => now(),
        ]);

        Log::info("[API] Bloque génesis creado: {$hash}");

        return response()->json([
            'mensaje' => 'Bloque génesis creado',
            'bloque'  => $bloque,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/health",
     *     tags={"Blockchain"},
     *     summary="Estado del nodo",
     *     @OA\Response(
     *         response=200,
     *         description="Estado del nodo",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="ok"),
     *             @OA\Property(property="node_id", type="string", example="nodo-laravel-8004"),
     *             @OA\Property(property="pendientes", type="integer", example=0),
     *             @OA\Property(property="bloques", type="integer", example=1)
     *         )
     *     )
     * )
     */
    public function health()
    {
        $bloques    = \App\Models\Grado::count();
        $pendientes = \App\Models\TransaccionPendiente::count();
        $nodeId     = env('NODE_ID', 'nodo-laravel-8004');

        Log::info("[API] GET /health - bloques: {$bloques}, pendientes: {$pendientes}");

        return response()->json([
            'status'     => 'ok',
            'node_id'    => $nodeId,
            'pendientes' => $pendientes,
            'bloques'    => $bloques,
        ]);
    }
}
