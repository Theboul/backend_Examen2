<?php

namespace App\Http\Controllers\Sistema;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sistema\Bitacora;
use App\Models\Usuarios\User;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\BitacoraExport;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpWord\PhpWord;
use Illuminate\Support\Facades\DB;

class BitacoraController extends Controller
{
    /**
     * Construye la consulta base
     */
    private function construirConsultaBitacora(Request $request)
    {
        // 1. Cargar la relación 'usuario' y su sub-relación 'perfil'
        $query = Bitacora::with('usuario.perfil'); 

        // 2. FILTRO (Busca por nombre de usuario O por nombre/apellido en perfil)
        if ($request->filled('usuario')) {
            $filtroUsuario = $request->usuario;
            $query->whereHas('usuario', function ($q) use ($filtroUsuario) {
                $q->where('usuario', 'ILIKE', "%{$filtroUsuario}%") // Buscar en el nombre de login
                  ->orWhereHas('perfil', function ($qp) use ($filtroUsuario) { // O buscar en el perfil
                      $qp->where('nombres', 'ILIKE', "%{$filtroUsuario}%")
                         ->orWhere('apellidos', 'ILIKE', "%{$filtroUsuario}%");
                  });
            });
        }

        if ($request->filled('accion')) {
            $query->where('accion', 'ILIKE', "%{$request->accion}%");
        }

        if ($request->filled('fecha')) {
            $query->whereDate('fecha', $request->fecha);
        }

        return $query;
    }

    /**
     * Listar registros de bitácora (PARA API JSON)
     */
    public function index(Request $request)
    {
        try {
            // 3. OBTENER PAGINADOR
            $bitacoras = $this->construirConsultaBitacora($request)
                               ->orderByDesc('id_bitacora')
                               ->paginate($request->input('page_size', 10));

            // 4. LÓGICA PARA DEVOLVER JSON (para tu React Service)
            if ($request->has('json') && $request->json == 1) {
                
                // 5. TRANSFORMACIÓN SEGURA (A PRUEBA DE NULOS)
                $bitacoras->getCollection()->transform(function ($b) {
                    $nombreUsuario = 'Anónimo'; // Valor por defecto
                    if ($b->usuario) {
                        $nombreUsuario = $b->usuario->perfil ? $b->usuario->perfil->nombre_completo : $b->usuario->usuario;
                    }
                    $b->usuario = $nombreUsuario; // Aplana el campo 'usuario' para el JSON
                    return $b;
                });

                return response()->json([
                    'success' => true,
                    'bitacoras' => $bitacoras->items(),
                    'current_page' => $bitacoras->currentPage(),
                    'last_page' => $bitacoras->lastPage(),
                    'total' => $bitacoras->total(),
                    'next_page_url' => $bitacoras->nextPageUrl(),
                    'prev_page_url' => $bitacoras->previousPageUrl(),
                ]);
            }
            
            // Comportamiento original (si no pide JSON, devuelve la vista Blade)
            $usuarios = User::join('perfil_usuario', 'users.id_usuario', '=', 'perfil_usuario.id_usuario')
                            ->orderBy('perfil_usuario.nombres')
                            ->pluck(DB::raw("CONCAT(nombres, ' ', apellidos) as nombre_completo"), 'users.id_usuario');
                            
            return view('pages.gestion.bitacoras.index', compact('bitacoras', 'usuarios'));

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Registrar evento en bitácora (puede llamarse desde cualquier parte)
     */
    public static function registrar($accion, $descripcion)
    {
        $usuario = Auth::user();
        $ip = request()->ip();

        if (!$usuario) return; // evita registrar si no hay usuario logueado

        Bitacora::create([
            'id_usuario' => $usuario->id_usuario,
            'accion' => $accion,
            'descripcion' => $descripcion,
            'fecha' => now(),
            'ip' => $ip,
        ]);
    }

    /**
     * Generar reporte en PDF, Excel o Word
     */
    public function getReport(Request $request)
    {
        // 1. Usar la misma lógica de consulta del index
        $bitacoras = $this->construirConsultaBitacora($request)
                           ->orderByDesc('id_bitacora')
                           ->get();
        
        // 2. Transformación segura (a prueba de nulos)
        $bitacoras->transform(function ($b) {
            $nombreUsuario = 'Anónimo';
            if ($b->usuario) {
                $nombreUsuario = $b->usuario->perfil ? $b->usuario->perfil->nombre_completo : $b->usuario->usuario;
            }
            $b->nombre_usuario_plano = $nombreUsuario; // Añadimos un campo nuevo para el reporte
            return $b;
        });

        $formato = $request->input('formato', 'pdf');

        try {
            return match ($formato) {
                'excel' => $this->exportExcel($bitacoras),
                'word'  => $this->exportWord($bitacoras),
                default => $this->exportPDF($bitacoras),
            };
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al generar el reporte: ' . $e->getMessage()], 500);
        }
    }

    // --- Métodos de Exportación ---

    protected function exportPDF($bitacoras)
    {
        // (Asegúrate de que esta vista '...bitacoraPDF' use $b->nombre_usuario_plano)
        $pdf = Pdf::loadView('reportes.bitacoraPDF', compact('bitacoras'));
        return $pdf->download('reporte_bitacora.pdf');
    }

    protected function exportExcel($bitacoras)
    {
        return Excel::download(new BitacoraExport($bitacoras), 'reporte_bitacora.xlsx');
    }

    protected function exportWord($bitacoras)
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $section->addText('Reporte de Bitácora');

        $table = $section->addTable();
        $table->addRow();
        $table->addCell()->addText('Acción');
        $table->addCell()->addText('Descripción');
        $table->addCell()->addText('Usuario');
        $table->addCell()->addText('IP');
        $table->addCell()->addText('Fecha');

        foreach ($bitacoras as $b) {
            $table->addRow();
            $table->addCell()->addText($b->accion);
            $table->addCell()->addText($b->descripcion ?? '-');
            $table->addCell()->addText($b->nombre_usuario_plano); // Usar el campo seguro
            $table->addCell()->addText($b->ip ?? '-');
            $table->addCell()->addText($b->fecha);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'word');
        $phpWord->save($tempFile, 'Word2007');
        return response()->download($tempFile, 'reporte_bitacora.docx')->deleteFileAfterSend(true);
    }
}