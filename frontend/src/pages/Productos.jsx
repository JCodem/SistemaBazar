import { useEffect, useState } from "react";
import axios from "axios";
import FormularioProducto from "../components/FormularioProducto"; // ajusta la ruta si está en otro directorio


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
            <div className="w-full max-w-5xl mx-auto bg-white dark:bg-gray-900 p-4 rounded-xl shadow-md">
                <h2 className="text-lg font-bold mb-2 text-gray-800 dark:text-white">Gestión de Productos</h2>
                <input
                    type="text"
                    placeholder="Buscar por nombre o código..."
                    value={busqueda}
                    onChange={(e) => buscarProductos(e.target.value)}
                    className="w-full p-2 border rounded dark:bg-gray-800 dark:border-gray-700"
                />
            </div>

            <div className="w-full max-w-5xl mx-auto bg-white dark:bg-gray-900 p-6 rounded-xl shadow-lg mt-6">
                <table className="min-w-full text-sm">
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


        </div>
    );
}
