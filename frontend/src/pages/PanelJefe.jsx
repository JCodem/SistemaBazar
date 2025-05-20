import Productos from "./Productos";
import FormularioProducto from "../components/FormularioProducto";
import GestionDia from "../components/GestionDia"; // Ajusta la ruta si es necesario


export default function PanelJefe() {
  return (
    <div>
      <h1 className="text-2xl font-bold">Bienvenido, Jefe de Ventas</h1>
      <p className="mt-2 text-gray-600">Selecciona una opción en el menú para comenzar.</p>

      <div className="flex flex-wrap justify-center gap-8 mt-6">
  <Productos />
  <GestionDia />

</div>
      

      
            </div>
  );
}


//<FormularioProducto/>   -->  tabla pero no en tiempo real.  