import { useState } from "react";
import { GestionService } from "../../features/Gestion/services/gestionService";

export default function GestionForm({ onCreated }: { onCreated: () => void }) {
  const [form, setForm] = useState({
    anio: "",
    semestre: "",
    fecha_inicio: "",
    fecha_fin: "",
  });
  const [loading, setLoading] = useState(false);

  const handleChange = (e: any) =>
    setForm({ ...form, [e.target.name]: e.target.value });

  const handleSubmit = async (e: any) => {
    e.preventDefault();
    setLoading(true);
    try {
      const res = await GestionService.crear(form);
      if (res.success) {
        alert("✅ Gestión creada correctamente");
        onCreated();
        setForm({ anio: "", semestre: "", fecha_inicio: "", fecha_fin: "" });
      } else {
        alert("⚠️ " + (res.message || "Error al crear la gestión"));
      }
    } catch (err: any) {
      alert("❌ Error al conectar con el servidor");
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  return (
    <form
      onSubmit={handleSubmit}
      className="bg-white shadow-lg rounded-xl p-6 border-t-4 border-blue-700"
    >
      <h2 className="text-lg font-bold text-blue-800 mb-4">
        Crear Gestión Académica
      </h2>
      <div className="grid grid-cols-2 gap-4">
        <input
          name="anio"
          type="number"
          placeholder="Año"
          value={form.anio}
          onChange={handleChange}
          className="input"
        />
        <select
          name="semestre"
          value={form.semestre}
          onChange={handleChange}
          className="input"
        >
          <option value="">Semestre</option>
          <option value="1">1</option>
          <option value="2">2</option>
        </select>
        <input
          type="date"
          name="fecha_inicio"
          value={form.fecha_inicio}
          onChange={handleChange}
          className="input"
        />
        <input
          type="date"
          name="fecha_fin"
          value={form.fecha_fin}
          onChange={handleChange}
          className="input"
        />
      </div>

      <button
        type="submit"
        disabled={loading}
        className="mt-4 w-full bg-blue-700 hover:bg-blue-800 text-white py-2 rounded-lg transition disabled:opacity-60"
      >
        {loading ? "Guardando..." : "Guardar Gestión"}
      </button>
    </form>
  );
}
