import api from "../../../../lib/axios"; // tu archivo axios global

export const GestionService = {
  async listar() {
    const res = await api.get("/gestiones");
    return res.data;
  },

  async crear(data: any) {
    const res = await api.post("/gestiones", data);
    return res.data;
  },

  async activar(id: number) {
    const res = await api.post(`/gestiones/${id}/activar`);
    return res.data;
  },

  async eliminar(id: number) {
    const res = await api.delete(`/gestiones/${id}`);
    return res.data;
  },

  async obtenerActiva() {
    const res = await api.get("/gestiones/activa");
    return res.data;
  },
};
