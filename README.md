# Blockchain Node - Laravel

Nodo independiente de una red blockchain distribuida para la gestión de grados académicos, implementado con Laravel 11 y PostgreSQL (Supabase).

## Descripción

Este nodo forma parte de una red blockchain distribuida donde cada integrante opera de forma autónoma pero interconectada. Cada nodo mantiene su propia cadena de bloques, gestiona transacciones pendientes, realiza el proceso de minado con Proof of Work y se sincroniza con los demás nodos mediante un algoritmo de consenso.

La lógica de negocio gestiona grados académicos, donde cada registro funciona como un bloque dentro de la cadena, integrando hash SHA256, hash anterior y nonce para garantizar la integridad de la información.

## Tecnologias

- PHP 8.4
- Laravel 11
- PostgreSQL (Supabase - Transaction Pooler)
- Eloquent ORM

## Requisitos

- PHP 8.1 o superior
- Composer 2.x
- Extensiones PHP: pgsql, pdo_pgsql, intl

## Instalacion

1. Clonar el repositorio:
```bash
git clone https://github.com/TU_USUARIO/blockchain-node-laravel.git
cd blockchain-node-laravel
```

2. Instalar dependencias:
```bash
composer install
```

3. Copiar el archivo de entorno:
```bash
cp .env.example .env
php artisan key:generate
```

4. Configurar las variables de entorno en `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=aws-1-us-east-1.pooler.supabase.com
DB_PORT=6543
DB_DATABASE=postgres
DB_USERNAME=postgres.TU_PROJECT_REF
DB_PASSWORD=TU_PASSWORD
DB_SSLMODE=require
SESSION_DRIVER=cookie
```

5. Verificar la conexion a la base de datos:
```bash
php artisan db:show
```

6. Levantar el servidor:
```bash
php artisan serve --port=8004
```

## Estructura del Proyecto
```
app/
├── Http/Controllers/
│   ├── BlockchainController.php   # Cadena, minado y recepcion de bloques
│   ├── TransactionController.php  # Gestion de transacciones
│   └── NodeController.php         # Registro de nodos y consenso
├── Models/
│   ├── Grado.php                  # Bloque de la cadena
│   ├── Persona.php
│   ├── Institucion.php
│   ├── Programa.php
│   ├── NivelGrado.php
│   ├── Nodo.php
│   └── TransaccionPendiente.php
└── Services/
    └── BlockchainService.php      # Logica central del blockchain
routes/
└── api.php                        # Definicion de endpoints
```

## API Endpoints

### Documentacion

| Metodo | Endpoint | Descripcion |
|--------|----------|-------------|
| GET | /api/documentation | accede a la documentacion de la API|

### Blockchain

| Metodo | Endpoint | Descripcion |
|--------|----------|-------------|
| GET | /chain | Obtener la cadena completa |
| POST | /mine | Minar transacciones pendientes |
| POST | /blocks/receive | Recibir bloque de otro nodo |

### Transacciones

| Metodo | Endpoint | Descripcion |
|--------|----------|-------------|
| POST | /transactions | Crear y propagar transaccion |
| GET | /transactions/pending | Listar transacciones pendientes |

### Nodos

| Metodo | Endpoint | Descripcion |
|--------|----------|-------------|
| POST | /nodes/register | Registrar un nodo en la red |
| GET | /nodes | Listar nodos registrados |
| GET | /nodes/resolve | Resolver conflictos por consenso |

## Logica Blockchain

### Hash

Cada bloque calcula su hash con SHA256 sobre la combinacion de:
```
SHA256(persona_id + institucion_id + titulo_obtenido + fecha_fin + hash_anterior + nonce)
```

### Proof of Work

El minado busca un nonce tal que el hash resultante inicie con `000`. El proceso itera incrementando el nonce hasta encontrar un hash valido.

### Consenso

Al invocar `/api/nodes/resolve`, el nodo consulta la cadena de todos los nodos registrados y adopta la cadena valida mas larga, garantizando consistencia distribuida.

### Propagacion

Al crear una transaccion o minar un bloque, el nodo propaga automaticamente la informacion a todos los nodos registrados en la red.

## Base de Datos

El esquema incluye las siguientes tablas en Supabase:

- `personas` - Datos del titular del grado
- `instituciones` - Instituciones educativas
`niveles_grado` - Niveles academicos (Licenciatura, Maestria, etc.)
- `programas` - Programas academicos
- `grados` - Bloques de la cadena con campos blockchain
- `nodos` - Nodos registrados en la red
- `transacciones_pendientes` - Transacciones en espera de minado

## Puertos de la Red

| Nodo | Tecnologia | Puerto |
|------|-----------|--------|
| Nodo 1 | Express | :8001 |
| Nodo 2 | Express | :8002 |
| Nodo 3 | Laravel | :8004 |
| Nodo 4 | Laravel | :8005 |

## Fases de Prueba

### Fase 1 - Setup
Levantar el nodo en el puerto asignado y verificar conexion a Supabase.

### Fase 2 - Registro
Registrar manualmente los nodos entre si para formar la red:
```bash
curl -X POST http://localhost:8004/api/nodes/register \
  -H "Content-Type: application/json" \
  -d '{"url": "http://localhost:8001"}'
```

### Fase 3 - Pruebas de Red
Crear una transaccion y verificar que llegue a los demas nodos. Minar en este nodo y verificar sincronizacion.

### Fase 4 - Consenso
Provocar conflictos minando en dos nodos simultaneamente y resolver con:
```bash
curl http://localhost:8004/api/nodes/resolve
```

## Logs

Todos los eventos importantes se registran en `storage/logs/laravel.log`:
```bash
tail -f storage/logs/laravel.log
```

Eventos registrados:
- Transacciones creadas y propagadas
- Bloques minados con nonce y hash
- Propagacion a otros nodos
- Proceso de consenso y reemplazo de cadena
- Nodos no disponibles durante propagacion
