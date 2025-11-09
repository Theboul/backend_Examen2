<?php

namespace App\Services;

use App\Models\Usuarios\User;
use App\Models\Usuarios\PerfilUsuario;
use App\Models\Usuarios\Rol;
use App\Models\Usuarios\Docente;
use App\Models\Maestros\TipoContrato;
use App\Models\Sistema\Bitacora;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Exception;

class CargaMasivaUsuariosService
{
    /**
     * Tiempo máximo de ejecución en segundos (10 minutos)
     */
    private const TIMEOUT_SECONDS = 600;

    /**
     * Columnas requeridas en el archivo CSV
     */
    private const COLUMNAS_REQUERIDAS = [
        'nombres',
        'apellidos',
        'ci',
        'email',
        'rol',
        'telefono',
    ];

    /**
     * Columnas opcionales
     */
    private const COLUMNAS_OPCIONALES = [
        'fecha_nacimiento',
        'genero',
        'cod_docente',
        'titulo',
        'especialidad',
        'grado_academico',
        'tipo_contrato',
        'fecha_ingreso',
    ];

    private $startTime;
    private $emailsEnArchivo = [];
    private $cisEnArchivo = [];
    private $emailsEnBD = [];
    private $cisEnBD = [];

    /**
     * Procesar archivo CSV de carga masiva de usuarios
     */
    public function procesarArchivo($archivo, $usuarioAdmin)
    {
        // Aumentar límites de tiempo de ejecución
        set_time_limit(600); // 10 minutos
        ini_set('max_execution_time', '600');
        
        $this->startTime = time();
        
        $resultado = [
            'total_procesados' => 0,
            'exitosos' => 0,
            'fallidos' => 0,
            'archivo' => $archivo->getClientOriginalName(),
            'usuarios_creados' => [],
            'errores' => [],
        ];

        DB::beginTransaction();

        try {
            // Cargar emails y CIs existentes en BD una sola vez
            $this->cargarDuplicadosExistentes();
            
            // Leer archivo CSV o Excel
            $filas = $this->leerArchivo($archivo);
            
            // Validar encabezados
            $encabezados = array_shift($filas);
            $this->validarEncabezados($encabezados);

            // Procesar cada fila
            foreach ($filas as $indice => $fila) {
                $numeroFila = $indice + 2; // +2 porque: +1 por encabezado, +1 por índice base 0

                // Verificar timeout
                if ($this->verificarTimeout()) {
                    throw new Exception('Timeout: El procesamiento excedió los 10 minutos permitidos');
                }

                // Procesar fila
                $resultadoFila = $this->procesarFila($fila, $encabezados, $numeroFila);
                
                $resultado['total_procesados']++;
                
                if ($resultadoFila['success']) {
                    $resultado['exitosos']++;
                    $resultado['usuarios_creados'][] = $resultadoFila['data'];
                } else {
                    $resultado['fallidos']++;
                    $resultado['errores'][] = [
                        'fila' => $numeroFila,
                        'datos' => $this->obtenerDatosRelevantes($fila, $encabezados),
                        'error' => $resultadoFila['error'],
                    ];
                }
            }

            // Registrar en bitácora
            $this->registrarEnBitacora($usuarioAdmin, $resultado);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Carga masiva completada',
                'data' => $resultado,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Error en carga masiva de usuarios', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Error en la carga masiva: ' . $e->getMessage(),
                'data' => $resultado,
            ];
        }
    }

    /**
     * Cargar emails y CIs existentes en BD una sola vez para optimizar
     */
    private function cargarDuplicadosExistentes(): void
    {
        // Cargar todos los emails de users y perfil_usuario
        $emailsUsers = User::pluck('email')->map(function($email) {
            return strtolower($email);
        })->toArray();
        
        $emailsPerfil = PerfilUsuario::pluck('email')->map(function($email) {
            return strtolower($email);
        })->toArray();
        
        $this->emailsEnBD = array_merge($emailsUsers, $emailsPerfil);
        
        // Cargar todos los CIs
        $this->cisEnBD = PerfilUsuario::pluck('ci')->toArray();
    }

    /**
     * Leer archivo CSV o Excel y convertir a array
     */
    private function leerArchivo($archivo): array
    {
        $extension = strtolower($archivo->getClientOriginalExtension());
        
        if (in_array($extension, ['xlsx', 'xls'])) {
            return $this->leerArchivoExcel($archivo);
        } else {
            return $this->leerArchivoCsv($archivo);
        }
    }

    /**
     * Leer archivo Excel y convertir a array
     */
    private function leerArchivoExcel($archivo): array
    {
        try {
            $spreadsheet = IOFactory::load($archivo->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $filas = [];

            foreach ($worksheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                
                $fila = [];
                foreach ($cellIterator as $cell) {
                    $valor = $cell->getValue();
                    
                    // Convertir fechas de Excel a formato string
                    if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
                        $valor = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($valor)->format('Y-m-d');
                    }
                    
                    $fila[] = trim((string)$valor);
                }
                
                // Omitir filas completamente vacías
                if (!empty(array_filter($fila))) {
                    $filas[] = $fila;
                }
            }

            return $filas;
            
        } catch (Exception $e) {
            throw new Exception('Error al leer el archivo Excel: ' . $e->getMessage());
        }
    }

    /**
     * Leer archivo CSV y convertir a array
     */
    private function leerArchivoCsv($archivo): array
    {
        $contenido = file_get_contents($archivo->getRealPath());
        
        // Detectar encoding y convertir a UTF-8 si es necesario
        $encoding = mb_detect_encoding($contenido, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding !== 'UTF-8') {
            $contenido = mb_convert_encoding($contenido, 'UTF-8', $encoding);
        }

        $filas = [];
        $lineas = explode("\n", $contenido);
        
        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (empty($linea)) {
                continue;
            }
            
            // Parsear CSV manualmente para manejar comillas y comas dentro de campos
            $filas[] = str_getcsv($linea, ',', '"');
        }

        return $filas;
    }

    /**
     * Validar que el archivo CSV tenga los encabezados correctos
     */
    private function validarEncabezados(array $encabezados): void
    {
        $encabezados = array_map('trim', $encabezados);
        $encabezados = array_map('strtolower', $encabezados);

        $faltantes = [];
        foreach (self::COLUMNAS_REQUERIDAS as $columna) {
            if (!in_array(strtolower($columna), $encabezados)) {
                $faltantes[] = $columna;
            }
        }

        if (!empty($faltantes)) {
            throw new Exception(
                'El archivo CSV no tiene las columnas requeridas: ' . implode(', ', $faltantes)
            );
        }
    }

    /**
     * Procesar una fila del CSV
     */
    private function procesarFila(array $fila, array $encabezados, int $numeroFila): array
    {
        try {
            // Crear array asociativo columna => valor
            $datos = $this->mapearDatos($fila, $encabezados);

            // Validar campos requeridos
            $erroresValidacion = $this->validarDatosRequeridos($datos);
            if (!empty($erroresValidacion)) {
                return [
                    'success' => false,
                    'error' => implode(', ', $erroresValidacion),
                ];
            }

            // Validar duplicados en BD
            if ($this->existeEmail($datos['email'])) {
                return [
                    'success' => false,
                    'error' => "El email '{$datos['email']}' ya existe en la base de datos",
                ];
            }

            if ($this->existeCi($datos['ci'])) {
                return [
                    'success' => false,
                    'error' => "El CI '{$datos['ci']}' ya existe en la base de datos",
                ];
            }

            // Validar duplicados en el mismo archivo
            if (in_array(strtolower($datos['email']), $this->emailsEnArchivo)) {
                return [
                    'success' => false,
                    'error' => "El email '{$datos['email']}' está duplicado en el archivo",
                ];
            }

            if (in_array($datos['ci'], $this->cisEnArchivo)) {
                return [
                    'success' => false,
                    'error' => "El CI '{$datos['ci']}' está duplicado en el archivo",
                ];
            }

            // Buscar rol
            $rol = Rol::where('nombre', $datos['rol'])->where('activo', true)->first();
            if (!$rol) {
                return [
                    'success' => false,
                    'error' => "El rol '{$datos['rol']}' no existe o está inactivo",
                ];
            }

            // Crear usuario
            $usuario = $this->crearUsuario($datos, $rol);
            
            // Crear perfil
            $this->crearPerfil($usuario, $datos);

            // Si es docente, crear registro de docente
            if (strtolower($datos['rol']) === 'docente') {
                $this->crearDocente($usuario, $datos);
            }

            // Marcar email y CI como procesados
            $this->emailsEnArchivo[] = strtolower($datos['email']);
            $this->cisEnArchivo[] = $datos['ci'];
            
            // Agregar a BD arrays para prevenir duplicados en el mismo batch
            $this->emailsEnBD[] = strtolower($datos['email']);
            $this->cisEnBD[] = $datos['ci'];

            // Generar nombre de usuario
            $nombreUsuario = $this->generarNombreUsuario($datos['nombres'], $datos['apellidos']);

            return [
                'success' => true,
                'data' => [
                    'fila' => $numeroFila,
                    'nombres' => $datos['nombres'],
                    'apellidos' => $datos['apellidos'],
                    'email' => $datos['email'],
                    'ci' => $datos['ci'],
                    'usuario' => $nombreUsuario,
                    'password_temporal' => $datos['ci'],
                    'rol' => $datos['rol'],
                ],
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Mapear datos de la fila a array asociativo
     */
    private function mapearDatos(array $fila, array $encabezados): array
    {
        $datos = [];
        foreach ($encabezados as $indice => $columna) {
            $columna = trim(strtolower($columna));
            $valor = isset($fila[$indice]) ? trim($fila[$indice]) : '';
            $datos[$columna] = $valor;
        }
        return $datos;
    }

    /**
     * Validar que los datos requeridos estén presentes
     */
    private function validarDatosRequeridos(array $datos): array
    {
        $errores = [];

        foreach (self::COLUMNAS_REQUERIDAS as $columna) {
            if (empty($datos[strtolower($columna)])) {
                $errores[] = "El campo '{$columna}' es requerido";
            }
        }

        // Validar formato de email
        if (!empty($datos['email']) && !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = "El email '{$datos['email']}' no tiene un formato válido";
        }

        // Validar CI (solo números)
        if (!empty($datos['ci']) && !preg_match('/^[0-9]+$/', $datos['ci'])) {
            $errores[] = "El CI debe contener solo números";
        }

        return $errores;
    }

    /**
     * Verificar si existe email en BD
     */
    private function existeEmail(string $email): bool
    {
        return in_array(strtolower($email), $this->emailsEnBD);
    }

    /**
     * Verificar si existe CI en BD
     */
    private function existeCi(string $ci): bool
    {
        return in_array($ci, $this->cisEnBD);
    }

    /**
     * Crear usuario
     */
    private function crearUsuario(array $datos, Rol $rol): User
    {
        $nombreUsuario = $this->generarNombreUsuario($datos['nombres'], $datos['apellidos']);

        return User::create([
            'id_rol' => $rol->id_rol,
            'usuario' => $nombreUsuario,
            'email' => $datos['email'],
            'password' => Hash::make($datos['ci']), // Contraseña temporal = CI
            'activo' => true,
            'primer_ingreso' => null, // Se marcará en el primer inicio de sesión
        ]);
    }

    /**
     * Crear perfil de usuario
     */
    private function crearPerfil(User $usuario, array $datos): PerfilUsuario
    {
        return PerfilUsuario::create([
            'id_usuario' => $usuario->id_usuario,
            'nombres' => $datos['nombres'],
            'apellidos' => $datos['apellidos'],
            'ci' => $datos['ci'],
            'email' => $datos['email'],
            'telefono' => $datos['telefono'],
            'fecha_nacimiento' => $this->parsearFecha($datos['fecha_nacimiento'] ?? null),
            'genero' => $datos['genero'] ?? null,
        ]);
    }

    /**
     * Crear registro de docente
     */
    private function crearDocente(User $usuario, array $datos): ?Docente
    {
        // Obtener código de docente
        $codDocente = !empty($datos['cod_docente']) 
            ? (int)$datos['cod_docente'] 
            : Docente::generarCodigoDocente();

        // Buscar tipo de contrato si se especificó
        $tipoContratoId = null;
        if (!empty($datos['tipo_contrato'])) {
            $tipoContrato = TipoContrato::where('nombre', $datos['tipo_contrato'])->first();
            if ($tipoContrato) {
                $tipoContratoId = $tipoContrato->id_tipo_contrato;
            }
        }

        return Docente::create([
            'cod_docente' => $codDocente,
            'id_usuario' => $usuario->id_usuario,
            'id_tipo_contrato' => $tipoContratoId,
            'titulo' => $datos['titulo'] ?? null,
            'especialidad' => $datos['especialidad'] ?? null,
            'grado_academico' => $datos['grado_academico'] ?? null,
            'activo' => true,
            'fecha_ingreso' => $this->parsearFecha($datos['fecha_ingreso'] ?? null),
        ]);
    }

    /**
     * Generar nombre de usuario único
     */
    private function generarNombreUsuario(string $nombres, string $apellidos): string
    {
        // Tomar primera letra del nombre y apellido completo
        $primerNombre = explode(' ', trim($nombres))[0];
        $primeraLetra = strtolower(substr($primerNombre, 0, 1));
        $apellidoLimpio = strtolower(str_replace(' ', '', $apellidos));
        
        $nombreBase = $primeraLetra . $apellidoLimpio;
        $nombreUsuario = $nombreBase;
        
        // Si existe, agregar número
        $contador = 1;
        while (User::where('usuario', $nombreUsuario)->exists()) {
            $nombreUsuario = $nombreBase . $contador;
            $contador++;
        }

        return $nombreUsuario;
    }

    /**
     * Parsear fecha en diferentes formatos
     */
    private function parsearFecha(?string $fecha): ?string
    {
        if (empty($fecha)) {
            return null;
        }

        try {
            // Intentar formato YYYY-MM-DD
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                return $fecha;
            }

            // Intentar formato DD/MM/YYYY
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $fecha, $matches)) {
                return "{$matches[3]}-{$matches[2]}-{$matches[1]}";
            }

            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Verificar si se excedió el timeout
     */
    private function verificarTimeout(): bool
    {
        return (time() - $this->startTime) > self::TIMEOUT_SECONDS;
    }

    /**
     * Obtener datos relevantes de una fila para mostrar en errores
     */
    private function obtenerDatosRelevantes(array $fila, array $encabezados): array
    {
        $datos = $this->mapearDatos($fila, $encabezados);
        return [
            'nombres' => $datos['nombres'] ?? '',
            'apellidos' => $datos['apellidos'] ?? '',
            'email' => $datos['email'] ?? '',
            'ci' => $datos['ci'] ?? '',
        ];
    }

    /**
     * Registrar operación en bitácora
     */
    private function registrarEnBitacora($usuarioAdmin, array $resultado): void
    {
        try {
            $descripcion = sprintf(
                "Carga masiva de usuarios - Archivo: %s | Procesados: %d | Exitosos: %d | Fallidos: %d",
                $resultado['archivo'],
                $resultado['total_procesados'],
                $resultado['exitosos'],
                $resultado['fallidos']
            );

            Bitacora::create([
                'id_usuario' => $usuarioAdmin->id_usuario ?? null,
                'accion' => 'CARGA_MASIVA_USUARIOS',
                'descripcion' => $descripcion,
                'ip' => request()->ip(),
                'fecha' => now(),
            ]);
        } catch (Exception $e) {
            Log::warning('No se pudo registrar en bitácora', ['error' => $e->getMessage()]);
        }
    }
}
