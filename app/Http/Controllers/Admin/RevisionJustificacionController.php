<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RevisionJustificacionController extends Controller
{
    public function indexPendientes()
    {
        return response()->json(['message' => 'Listado de justificaciones pendientes (demo)']);
    }

    public function revisar($id)
    {
        return response()->json(['message' => "Justificación $id revisada (demo)"]);
    }
}
