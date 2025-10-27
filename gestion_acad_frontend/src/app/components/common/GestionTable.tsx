import { useEffect, useState } from "react";
import { GestionService } from "../../features/Gestion/services/gestionService";

export default function GestionTable({ refresh }: { refresh: boolean }) {
  const [gestiones, setGestiones] = useState<any[]>([]);

  const cargarGestiones = async () => {
    try {
      const res = await GestionService.listar();
      if (res.success) setGestiones(res.data);
    } catch (err) {
      console.error("Error al obtener gestiones:", err);
    }
  };

  useEffect(() => {
    cargarGestiones();
  }, [refresh]);

  const activar = async (id: number) => {
    const res = await GestionService.activar(id);
    alert(res.message);
    cargarGestiones();
  };

  const eliminar = async (id: number) => {
    if (confirm("¿Deseas eliminar esta gestión?")) {
      const res = await GestionService.eliminar(id);
      alert(res.message);
      cargarGestiones();
    }
  };

  return (
    <div className="bg-white shadow-md rounded-xl p-4 mt-6">
      <h2 className="text-blue-800 font-semibold mb-3">
        Gestiones Registradas
      </h2>
      <table className="w-full text-sm text-center border">
        <thead className="bg-blue-700 text-white">
          <tr>
            <th className="py-2">Año</th>
            <th>Semestre</th>
            <th>Inicio</th>
            <th>Fin</th>
            <th>Activo</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          {gestiones.map((g) => (
            <tr key={g.id_gestion} className="border-b hover:bg-sky-50">
              <td>{g.anio}</td>
              <td>{g.semestre}</td>
              <td>{g.fecha_inicio}</td>
              <td>{g.fecha_fin}</td>
              <td>
                <span
                  className={`px-2 py-1 rounded-full text-white ${
                    g.activo ? "bg-green-600" : "bg-gray-400"
                  }`}
                >
                  {g.activo ? "Activa" : "Inactiva"}
                </span>
              </td>
              <td className="space-x-2">
                <button
                  onClick={() => activar(g.id_gestion)}
                  className="bg-blue-600 text-white px-3 py-1 rounded-lg hover:bg-blue-700"
                >
                  Activar
                </button>
                <button
                  onClick={() => eliminar(g.id_gestion)}
                  className="bg-red-600 text-white px-3 py-1 rounded-lg hover:bg-red-700"
                >
                  Eliminar
                </button>
              </td>
            </tr>
          ))}
          {gestiones.length === 0 && (
            <tr>
              <td colSpan={6} className="py-3 text-gray-500">
                No hay gestiones registradas
              </td>
            </tr>
          )}
        </tbody>
      </table>
    </div>
  );
}
