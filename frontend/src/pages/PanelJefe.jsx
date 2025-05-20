import GestionDia from "../components/GestionDia";
import FormularioProducto from "../components/FormularioProducto";
import Productos from "./Productos";

export default function PanelJefe() {
  return (
    <div className="max-w-7xl mx-auto px-4 mt-6">
      <h1 className="text-2xl font-bold">Bienvenido, Jefe de Ventas</h1>
      <p className="mt-2 text-gray-600">Selecciona una opción en el menú para comenzar.</p>

      {/* Contenedor de dos columnas */}
      <div className="flex flex-wrap md:flex-nowrap justify-start gap-6 mt-8">
        {/* Columna izquierda: Gestión del Día + Registrar Producto */}
        <div className="w-full md:w-1/3 lg:w-1/4 space-y-6">
          <GestionDia />
          <FormularioProducto />
        </div>

        {/* Columna derecha: Gestión de productos */}
        <div className="w-full md:w-2/3 lg:w-3/4">
          <Productos />
        </div>
      </div>
    </div>
  );
}
