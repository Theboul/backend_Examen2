<?php

namespace App\Http\Controllers\Sistema;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sistema\Bitacora;
use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\BitacoraExport;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpWord\PhpWord;

class BitacoraController extends Controller
{
    /**
     * Listar registros de bitácora con filtros opcionales
     */
    public function index(Request $request)
    {
        $query = Bitacora::with('usuario:id_usuario,nombre_usuario,email');

        if ($request->filled('usuario')) {
            $query->whereHas('usuario', function ($q) use ($request) {
                $q->where('nombre_usuario', 'like', "%{$request->usuario}%");
            });
        }

        if ($request->filled('accion')) {
            $query->where('accion', 'like', "%{$request->accion}%");
        }

        if ($request->filled('fecha')) {
            $query->whereDate('fecha', $request->fecha);
        }

        $bitacoras = $query->orderByDesc('id_bitacora')->paginate(10);

        $usuarios = Usuario::orderBy('nombre_usuario')->pluck('nombre_usuario', 'id_usuario');

        return view('pages.gestion.bitacoras.index', compact('bitacoras', 'usuarios'));
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
        $bitacoras = Bitacora::with('usuario:id_usuario,nombre_usuario')
            ->when($request->usuario, fn($q) => 
                $q->whereHas('usuario', fn($u) => $u->where('nombre_usuario', 'like', "%{$request->usuario}%"))
            )
            ->when($request->accion, fn($q) => $q->where('accion', 'like', "%{$request->accion}%"))
            ->when($request->fecha, fn($q) => $q->whereDate('fecha', $request->fecha))
            ->orderByDesc('id_bitacora')
            ->get();

        $formato = $request->input('formato', 'pdf');

        return match ($formato) {
            'excel' => $this->exportExcel($bitacoras),
            'word'  => $this->exportWord($bitacoras),
            default => $this->exportPDF($bitacoras),
        };
    }

    protected function exportPDF($bitacoras)
    {
        $pdf = Pdf::loadView('pages.gestion.reportes.bitacoraPDF', compact('bitacoras'));
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
            $table->addCell()->addText($b->usuario->nombre_usuario ?? '-');
            $table->addCell()->addText($b->ip ?? '-');
            $table->addCell()->addText($b->fecha);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'word');
        $phpWord->save($tempFile, 'Word2007');
        return response()->download($tempFile, 'reporte_bitacora.docx')->deleteFileAfterSend(true);
    }
}
