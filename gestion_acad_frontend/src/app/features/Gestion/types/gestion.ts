export interface Gestion {
  id_gestion: number;
  anio: number;
  semestre: number;
  fecha_inicio: string;
  fecha_fin: string;
  activo: boolean;
}

export type CreateGestionData = Omit<Gestion, 'id_gestion' | 'activo'>;