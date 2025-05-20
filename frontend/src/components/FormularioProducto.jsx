import { useState } from "react";
import axios from "axios";

export default function FormularioProducto() {
  const [formulario, setFormulario] = useState({
    nombre: "",
    codigo: "",
    valor: "",
  });

  const [mensaje, setMensaje] = useState("");

  const handleChange = (e) => {
    setFormulario({ ...formulario, [e.target.name]: e.target.value });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      await axios.post("http://localhost:5000/api/productos", formulario);
      setMensaje("Producto creado con éxito 🎉");
      setFormulario({ nombre: "", codigo: "", valor: "" });
    } catch (error) {
      setMensaje("Error al crear producto ❌");
    }
  };

  return (
    <div className="max-w-md mx-auto bg-white dark:bg-gray-900 p-6 rounded-xl shadow-lg mt-10">
      <h2 className="text-2xl font-bold mb-4 text-center text-gray-800 dark:text-white">
        Registrar Producto
      </h2>
      <form onSubmit={handleSubmit} className="space-y-4">
        <input
          type="text"
          name="nombre"
          value={formulario.nombre}
          onChange={handleChange}
          placeholder="Nombre del producto"
          className="w-full px-4 py-2 rounded border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
          required
        />
        <input
          type="text"
          name="codigo"
          value={formulario.codigo}
          onChange={handleChange}
          placeholder="Código del producto"
          className="w-full px-4 py-2 rounded border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
          required
        />
        <input
          type="number"
          name="valor"
          value={formulario.valor}
          onChange={handleChange}
          placeholder="Valor unitario"
          className="w-full px-4 py-2 rounded border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
          required
        />
         <input
          type="text"
          name="descripcion"
          value={formulario.descripcion}
          onChange={handleChange}
          placeholder="Descripción del producto"
          className="w-full px-4 py-2 rounded border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
          required
        />
        <button
          type="submit"
          className="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded"
        >
          Guardar Producto
        </button>
      </form>
      {mensaje && (
        <p className="text-center mt-4 text-sm text-green-500 dark:text-green-400">{mensaje}</p>
      )}
    </div>
  );
}
