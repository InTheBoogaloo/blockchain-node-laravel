<?php

namespace App\Services;

use App\Models\Grado;
use App\Models\Nodo;
use App\Models\TransaccionPendiente;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BlockchainService
{
    const DIFICULTAD = '000';

    // ─── Cadena ───────────────────────────────────────────────

    public function obtenerCadena(): array
    {
        return Grado::orderBy('creado_en', 'asc')->get()->toArray();
    }

    public function obtenerUltimoBloque(): ?Grado
    {
        return Grado::orderBy('creado_en', 'desc')->first();
    }

    // ─── Hash ─────────────────────────────────────────────────

    public function calcularHash(
        string $personaId,
        string $institucionId,
        string $tituloObtenido,
        string $fechaFin,
        string $hashAnterior,
        int $nonce
    ): string {
        $datos = $personaId . $institucionId . $tituloObtenido . $fechaFin . $hashAnterior . $nonce;
        return hash('sha256', $datos);
    }

    // ─── Proof of Work ────────────────────────────────────────

    public function proofOfWork(
        string $personaId,
        string $institucionId,
        string $tituloObtenido,
        string $fechaFin,
        string $hashAnterior
    ): array {
        $nonce = 0;
        do {
            $hash = $this->calcularHash(
                $personaId,
                $institucionId,
                $tituloObtenido,
                $fechaFin,
                $hashAnterior,
                $nonce
            );
            $nonce++;
        } while (!str_starts_with($hash, self::DIFICULTAD));

        Log::info("[Blockchain] PoW completado - nonce: {$nonce}, hash: {$hash}");

        return ['nonce' => $nonce - 1, 'hash' => $hash];
    }

    // ─── Validación de bloque ─────────────────────────────────

    public function validarBloque(array $bloque, ?array $bloqueAnterior): bool
    {
        // Verificar hash anterior
        $hashEsperado = $bloqueAnterior ? $bloqueAnterior['hash_actual'] : '';
        if ($bloque['hash_anterior'] !== $hashEsperado) {
            Log::warning("[Blockchain] Hash anterior inválido en bloque: {$bloque['id']}");
            return false;
        }

        // Recalcular hash
        $hashCalculado = $this->calcularHash(
            $bloque['persona_id'],
            $bloque['institucion_id'],
            $bloque['titulo_obtenido'],
            $bloque['fecha_fin'],
            $bloque['hash_anterior'],
            (int) $bloque['nonce']
        );

        if ($hashCalculado !== $bloque['hash_actual']) {
            Log::warning("[Blockchain] Hash actual inválido en bloque: {$bloque['id']}");
            return false;
        }

        // Verificar Proof of Work
        if (!str_starts_with($bloque['hash_actual'], self::DIFICULTAD)) {
            Log::warning("[Blockchain] PoW inválido en bloque: {$bloque['id']}");
            return false;
        }

        return true;
    }

    // ─── Validación de cadena completa ────────────────────────

    public function validarCadena(array $cadena): bool
    {
        if (empty($cadena)) return true;

        for ($i = 1; $i < count($cadena); $i++) {
            if (!$this->validarBloque($cadena[$i], $cadena[$i - 1])) {
                return false;
            }
        }

        return true;
    }

    // ─── Transacciones pendientes ─────────────────────────────

    public function agregarTransaccion(array $datos): TransaccionPendiente
    {
        $transaccion = TransaccionPendiente::create([
            'id'              => Str::uuid(),
            'persona_id'      => $datos['persona_id'],
            'institucion_id'  => $datos['institucion_id'],
            'programa_id'     => $datos['programa_id'],
            'titulo_obtenido' => $datos['titulo_obtenido'],
            'fecha_inicio'    => $datos['fecha_inicio'] ?? null,
            'fecha_fin'       => $datos['fecha_fin'],
            'numero_cedula'   => $datos['numero_cedula'] ?? null,
            'titulo_tesis'    => $datos['titulo_tesis'] ?? null,
            'menciones'       => $datos['menciones'] ?? null,
            'creado_en'       => now(),
        ]);

        Log::info("[Blockchain] Transacción agregada: {$transaccion->id}");
        return $transaccion;
    }

    public function obtenerTransaccionesPendientes(): array
    {
        return TransaccionPendiente::all()->toArray();
    }

    // ─── Minado ───────────────────────────────────────────────

    public function minar(string $firmadoPor): array
    {
        $pendientes = TransaccionPendiente::all();

        if ($pendientes->isEmpty()) {
            return ['error' => 'No hay transacciones pendientes para minar'];
        }

        $ultimoBloque = $this->obtenerUltimoBloque();
        $hashAnterior = $ultimoBloque ? $ultimoBloque->hash_actual : '';

        $bloquesMineados = [];

        foreach ($pendientes as $tx) {
            $pow = $this->proofOfWork(
                $tx->persona_id,
                $tx->institucion_id,
                $tx->titulo_obtenido,
                $tx->fecha_fin,
                $hashAnterior
            );

            $grado = Grado::create([
                'id'              => Str::uuid(),
                'persona_id'      => $tx->persona_id,
                'institucion_id'  => $tx->institucion_id,
                'programa_id'     => $tx->programa_id,
                'titulo_obtenido' => $tx->titulo_obtenido,
                'fecha_inicio'    => $tx->fecha_inicio,
                'fecha_fin'       => $tx->fecha_fin,
                'numero_cedula'   => $tx->numero_cedula,
                'titulo_tesis'    => $tx->titulo_tesis,
                'menciones'       => $tx->menciones,
                'hash_actual'     => $pow['hash'],
                'hash_anterior'   => $hashAnterior,
                'nonce'           => $pow['nonce'],
                'firmado_por'     => $firmadoPor,
                'creado_en'       => now(),
            ]);

            $hashAnterior = $pow['hash'];
            $bloquesMineados[] = $grado->toArray();
            $tx->delete();

            Log::info("[Blockchain] Bloque minado: {$grado->id} | hash: {$pow['hash']}");
        }

        return $bloquesMineados;
    }

    // ─── Consenso ─────────────────────────────────────────────

    public function resolverConflictos(): array
    {
        $nodos = Nodo::all();
        $cadenaActual = $this->obtenerCadena();
        $longitudActual = count($cadenaActual);
        $reemplazada = false;

        foreach ($nodos as $nodo) {
            try {
                // FIX: usar /api/chain (con prefijo)
                $response = Http::timeout(5)->get("{$nodo->url}/api/chain");

                if ($response->successful()) {
                    $data = $response->json();
                    $cadenaRemota = $data['chain'] ?? [];
                    $longitudRemota = count($cadenaRemota);

                    if ($longitudRemota > $longitudActual && $this->validarCadena($cadenaRemota)) {
                        Log::info("[Consenso] Cadena más larga encontrada en: {$nodo->url} ({$longitudRemota} bloques)");
                        $this->reemplazarCadena($cadenaRemota);
                        $cadenaActual = $cadenaRemota;
                        $longitudActual = $longitudRemota;
                        $reemplazada = true;
                    }
                }
            } catch (\Exception $e) {
                Log::warning("[Consenso] Nodo no disponible: {$nodo->url} - {$e->getMessage()}");
            }
        }

        return [
            'reemplazada' => $reemplazada,
            'cadena'      => $cadenaActual,
        ];
    }

    private function reemplazarCadena(array $cadena): void
    {
        Grado::truncate();
        foreach ($cadena as $bloque) {
            Grado::create($bloque);
        }
        Log::info("[Consenso] Cadena local reemplazada con " . count($cadena) . " bloques");
    }

    // ─── Propagación ──────────────────────────────────────────

    public function propagarTransaccion(array $datos): void
    {
        $nodos = Nodo::all();
        foreach ($nodos as $nodo) {
            try {
                // FIX: usar /api/transactions (con prefijo)
                Http::timeout(5)->post("{$nodo->url}/api/transactions", $datos);
                Log::info("[Propagación] Transacción enviada a: {$nodo->url}");
            } catch (\Exception $e) {
                Log::warning("[Propagación] Error enviando a {$nodo->url}: {$e->getMessage()}");
            }
        }
    }

    public function propagarBloque(array $bloque): void
    {
        $nodos = Nodo::all();
        foreach ($nodos as $nodo) {
            try {
                // FIX: usar /api/blocks/receive (con prefijo)
                Http::timeout(5)->post("{$nodo->url}/api/blocks/receive", $bloque);
                Log::info("[Propagación] Bloque enviado a: {$nodo->url}");
            } catch (\Exception $e) {
                Log::warning("[Propagación] Error enviando bloque a {$nodo->url}: {$e->getMessage()}");
            }
        }
    }
}
