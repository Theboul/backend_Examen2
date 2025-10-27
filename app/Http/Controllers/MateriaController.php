<?php

namespace App\Http\Controllers;

use App\Models\Materia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MateriaController extends Controller
{
    /**
     * Listar materias (por defecto solo activas).
     * ?incluir_inactivas=true para traer todas.
     */
    public function index(Request $request)
    {
        try {
            $q = Materia::query()
                ->with(['carrera:id_carrera,nombre,codigo', 'semestre:id_semestre,nombre']);

            if (!$request->boolean('incluir_inactivas', false)) {
                $q->activas();
            }

            $materias = $q->orderBy('id_carrera')->orderBy('id_semestre')->orderBy('nombre')->get();

            return response()->json([
                'success' => true,
                'data' => $materias,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las materias',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ver una materia específica.
     */
    public function show($id)
    {
        $m = Materia::with(['carrera', 'semestre'])->find($id);
        if (!$m) {
            return response()->json(['success' => false, 'message' => 'Materia no encontrada'], 404);
        }
        return response()->json(['success' => true, 'data' => $m]);
    }

    /**
     * Crear nueva materia.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_semestre' => ['required', 'integer', 'exists:semestre,id_semestre'],
            'id_carrera'  => ['required', 'integer', 'exists:carrera,id_carrera'],
            'nombre'      => ['required', 'string', 'max:150'],
            'sigla'       => ['nullable', 'string', 'max:10', 'unique:materia,sigla'],
            'creditos'    => ['nullable', 'integer', 'min:0', 'max:20'],
            'carga_horaria_semestral' => ['nullable', 'integer', 'min:0', 'max:400'],
        ]);

        DB::beginTransaction();

        $materia = Materia::create($validated + ['activo' => true]);
        //$this->registrarBitacora('Materia creada: ' . $materia->sigla . ' - ' . $materia->nombre);

        DB::commit();

        return response()->json([
            'success' => true,
            'data' => $materia,
            'message' => 'Materia registrada exitosamente'
        ], 201);
    }

    /**
     * Actualizar materia existente.
     */
    public function update(Request $request, $id)
    {
        $materia = Materia::find($id);
        if (!$materia) {
            return response()->json(['success' => false, 'message' => 'Materia no encontrada'], 404);
        }

        $validated = $request->validate([
            'id_semestre' => ['required', 'integer', 'exists:semestre,id_semestre'],
            'id_carrera'  => ['required', 'integer', 'exists:carrera,id_carrera'],
            'nombre'      => ['required', 'string', 'max:150'],
            'sigla'       => ['nullable', 'string', 'max:10', Rule::unique('materia', 'sigla')->ignore($id, 'id_materia')],
            'creditos'    => ['nullable', 'integer', 'min:0', 'max:20'],
            'carga_horaria_semestral' => ['nullable', 'integer', 'min:0', 'max:400'],
            'activo'      => ['sometimes', 'boolean'],
        ]);

        DB::beginTransaction();
        $materia->update($validated);
        //$this->registrarBitacora('Materia actualizada: ' . $materia->sigla . ' - ' . $materia->nombre);
        DB::commit();

        return response()->json([
            'success' => true,
            'data' => $materia,
            'message' => 'Materia actualizada exitosamente'
        ]);
    }

    /**
     * Desactivar (soft delete).
     */
    public function destroy($id)
    {
        // Comentado temporalmente hasta tener los modelos Grupo y MateriaGrupo
        // $materia = Materia::with(['grupos', 'materiaGrupos'])->find($id);
        $materia = Materia::find($id);

        if (!$materia) {
            return response()->json(['success' => false, 'message' => 'Materia no encontrada'], 404);
        }

        if (!$materia->puedeDesactivarse()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede desactivar. La materia tiene grupos o asignaciones activas.'
            ], 400);
        }

        DB::beginTransaction();
        $materia->update(['activo' => false]);
        //$this->registrarBitacora('Materia desactivada: ' . $materia->sigla . ' - ' . $materia->nombre);
        DB::commit();

        return response()->json(['success' => true, 'message' => 'Materia desactivada exitosamente']);
    }

    /**
     * Reactivar materia.
     */
    public function reactivar($id)
    {
        $materia = Materia::inactivas()->find($id);
        if (!$materia) {
            return response()->json(['success' => false, 'message' => 'Materia no encontrada o ya está activa'], 404);
        }

        DB::beginTransaction();
        $materia->update(['activo' => true]);
        //$this->registrarBitacora('Materia reactivada: ' . $materia->sigla . ' - ' . $materia->nombre);
        DB::commit();

        return response()->json([
            'success' => true,
            'data' => $materia,
            'message' => 'Materia reactivada exitosamente'
        ]);
    }

    /**
     * Obtener materias activas para combos.
     */
    public function getMateriasForSelect(Request $request)
    {
        $byCarrera = $request->integer('id_carrera');
        $q = Materia::activas()->select('id_materia', 'nombre', 'sigla')->orderBy('nombre');

        if ($byCarrera) $q->where('id_carrera', $byCarrera);

        $items = $q->get()->map(fn($m) => [
            'value' => $m->id_materia,
            'label' => $m->nombre . ' (' . ($m->sigla ?? $m->codigo) . ')'
        ]);

        return response()->json(['success' => true, 'data' => $items]);
    }

    /**
     * Registrar acción en bitácora.
     */
    private function registrarBitacora($accion)
    {
        try {
            DB::table('bitacora')->insert([
                'id_perfil_usuario' => auth()->user()->id_perfil_usuario ?? 1, // temporal
                'accion' => $accion,
                'fecha' => now(),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error al registrar bitácora: ' . $e->getMessage());
        }
    }
}
