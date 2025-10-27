import { useState } from "react";
import GestionForm from "../../../app/components/common/GestionForm";
import GestionTable from "../../../app/components/common/GestionTable";

export default function GestionPage() {
  const [refresh, setRefresh] = useState(false);

  return (
    <div className="min-h-screen bg-sky-100 p-6">
      <h1 className="text-2xl font-bold text-blue-800 mb-6 border-b-4 border-red-600 pb-2">
        Gestión de Gestiones Académicas
      </h1>
      <GestionForm onCreated={() => setRefresh(!refresh)} />
      <GestionTable refresh={refresh} />
    </div>
  );
}

