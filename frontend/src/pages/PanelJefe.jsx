import Productos from "./Productos";

export default function PanelJefe() {
  return (
    <div>
      <h1 className="text-2xl font-bold">Bienvenido, Jefe de Ventas</h1>
      <p className="mt-2 text-gray-600">Selecciona una opción en el menú para comenzar.</p>
      <Productos/>    
      </div>
  );
}
