import Sidebar from "../components/Sidebar";
import PanelJefe from "../pages/PanelJefe";
import PanelVendedor from "../pages/PanelVendedor";

export default function DashboardLayout() {
  const rol = localStorage.getItem("rol");

  return (
    <div className="flex min-h-screen bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100">
      <Sidebar rol={rol} />
      <main className="flex-1 p-6">
        {rol === "jefe" ? <PanelJefe /> : <PanelVendedor />}
      </main>
    </div>
  );
}
