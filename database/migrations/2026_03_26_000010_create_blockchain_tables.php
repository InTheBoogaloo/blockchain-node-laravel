<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ─── personas ────────────────────────────────────────
        Schema::create('personas', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('nombre', 100);
            $table->string('apellido_paterno', 100);
            $table->string('apellido_materno', 100)->nullable();
            $table->string('curp', 18)->unique()->nullable();
            $table->string('correo', 150)->nullable();
            $table->timestamp('creado_en')->useCurrent();
        });

        // ─── instituciones ───────────────────────────────────
        Schema::create('instituciones', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('nombre', 255);
            $table->string('pais', 100)->nullable();
            $table->string('estado', 100)->nullable();
            $table->timestamp('creado_en')->useCurrent();
        });

        // ─── niveles_grado ───────────────────────────────────
        Schema::create('niveles_grado', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 50);
        });

        DB::table('niveles_grado')->insert([
            ['nombre' => 'Técnico'],
            ['nombre' => 'Licenciatura'],
            ['nombre' => 'Maestría'],
            ['nombre' => 'Doctorado'],
            ['nombre' => 'Especialidad'],
        ]);

        // ─── programas ───────────────────────────────────────
        Schema::create('programas', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('nombre', 255);
            $table->unsignedInteger('nivel_grado_id')->nullable();
            $table->foreign('nivel_grado_id')->references('id')->on('niveles_grado');
            $table->timestamp('creado_en')->useCurrent();
        });

        // ─── grados (bloques de la cadena) ───────────────────
        Schema::create('grados', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('persona_id')->nullable();
            $table->foreign('persona_id')->references('id')->on('personas')->onDelete('cascade');
            $table->uuid('institucion_id')->nullable();
            $table->foreign('institucion_id')->references('id')->on('instituciones');
            $table->uuid('programa_id')->nullable();
            $table->foreign('programa_id')->references('id')->on('programas');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->string('titulo_obtenido', 255)->nullable();
            $table->string('numero_cedula', 50)->nullable();
            $table->text('titulo_tesis')->nullable();
            $table->string('menciones', 100)->nullable();
            // Campos blockchain
            $table->text('hash_actual');
            $table->text('hash_anterior')->nullable();
            $table->integer('nonce')->nullable();
            $table->string('firmado_por', 255)->nullable();
            $table->timestamp('creado_en')->useCurrent();
        });

        // ─── nodos ───────────────────────────────────────────
        Schema::create('nodos', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('url', 255)->unique();
            $table->timestamp('creado_en')->useCurrent();
        });

        // ─── transacciones_pendientes ────────────────────────
        Schema::create('transacciones_pendientes', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('persona_id');
            $table->uuid('institucion_id');
            $table->uuid('programa_id');
            $table->string('titulo_obtenido', 255);
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin');
            $table->string('numero_cedula', 50)->nullable();
            $table->text('titulo_tesis')->nullable();
            $table->string('menciones', 100)->nullable();
            $table->timestamp('creado_en')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transacciones_pendientes');
        Schema::dropIfExists('nodos');
        Schema::dropIfExists('grados');
        Schema::dropIfExists('programas');
        Schema::dropIfExists('niveles_grado');
        Schema::dropIfExists('instituciones');
        Schema::dropIfExists('personas');
    }
};
