import { useEffect, useState } from "react";
import axios from "axios";

export default function Productos() {
    const [productos, setProductos] = useState([]);
    const [formulario, setFormulario] = useState({
        nombre: "",
        codigo: "",
        valor: "",
        descripcion: ""
    });

    const rol = localStorage.getItem("rol"); 3
    const [busqueda, setBusqueda] = useState("");


    const buscarProductos = async (texto) => {
        setBusqueda(texto);
        if (texto.trim() === "") {
            obtenerProductos(); // muestra todos
            return;
        }

        try {
            const res = await axios.get(`http://localhost:5000/api/productos/buscar?q=${texto}`);
            setProductos(res.data);
        } catch (err) {
            console.error("Error al buscar:", err);
        }
    };

    const obtenerProductos = async () => {
        const res = await axios.get("http://localhost:5000/api/productos");
        setProductos(res.data);
    };

    useEffect(() => {
        obtenerProductos();
    }, []);

    const handleChange = (e) => {
        setFormulario({ ...formulario, [e.target.name]: e.target.value });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        await axios.post("http://localhost:5000/api/productos", formulario);
        setFormulario({ nombre: "", codigo: "", valor: "", descripcion: "" });
        obtenerProductos();
    };

    return (
        <div>
            <h1 className="text-xl font-bold mb-4">Gestión de Productos</h1>

            <div className="mb-4">
                <input
                    type="text"
                    placeholder="Buscar por nombre o código..."
                    value={busqueda}
                    onChange={(e) => buscarProductos(e.target.value)}
                    className="w-full p-2 border rounded dark:bg-gray-800 dark:border-gray-700"
                />
            </div>

            {rol === "jefe" && (
                <form onSubmit={handleSubmit} className="bg-white dark:bg-gray-800 p-4 mb-6 rounded shadow-md space-y-3">
                    <div className="flex flex-col gap-2">
                        <input name="nombre" placeholder="Nombre" value={formulario.nombre} onChange={handleChange} className="input" />
                        <input name="codigo" placeholder="Código" value={formulario.codigo} onChange={handleChange} className="input" />
                        <input name="valor" placeholder="Valor" type="number" value={formulario.valor} onChange={handleChange} className="input" />
                        <textarea name="descripcion" placeholder="Descripción" value={formulario.descripcion} onChange={handleChange} className="input" />
                    </div>
                    <button className="bg-blue-600 hover:bg-blue-700 text-white py-1 px-4 rounded">
                        Agregar Producto
                    </button>
                </form>
            )}

            <table className="min-w-full bg-white dark:bg-gray-800 text-sm">
                <thead>
                    <tr className="bg-gray-200 dark:bg-gray-700 text-left">
                        <th className="p-2">#</th>
                        <th className="p-2">Nombre</th>
                        <th className="p-2">Código</th>
                        <th className="p-2">Valor</th>
                        <th className="p-2">Descripción</th>
                    </tr>
                </thead>
                <tbody>
                    {productos.map((prod, index) => (
                        <tr key={prod.id} className="border-t dark:border-gray-700">
                            <td className="p-2">{index + 1}</td>
                            <td className="p-2">{prod.nombre}</td>
                            <td className="p-2">{prod.codigo}</td>
                            <td className="p-2">${prod.valor}</td>
                            <td className="p-2">{prod.descripcion}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
