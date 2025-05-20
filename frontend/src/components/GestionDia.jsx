import { useEffect, useState } from "react";
import axios from "axios";
import { motion, AnimatePresence } from "framer-motion";

export default function GestionDia() {
    const [estadoDia, setEstadoDia] = useState("desconocido");
    const [mensaje, setMensaje] = useState("");
    const rol = localStorage.getItem("rol");

    const obtenerEstadoDia = async () => {
        try {
            const res = await axios.get("http://localhost:5000/api/dia/hoy");
            setEstadoDia(res.data.estado);
        } catch (error) {
            console.error("Error al obtener el estado del día:", error);
        }
    };

    const cambiarEstado = async (nuevoEstado) => {
        try {
            const url = `http://localhost:5000/api/dia/${nuevoEstado}`;
            await axios.post(url);

            const mensajes = {
                abrir: "¡El día ha comenzado! ¡Vamos con todo! ☀️",
                cerrar: "Día cerrado, hasta mañana 🌙",
            };

            setMensaje(mensajes[nuevoEstado]);
            obtenerEstadoDia();

            // Ocultar el mensaje después de 3 segundos
            setTimeout(() => {
                setMensaje("");
            }, 3000);
        } catch (error) {
            setMensaje("❌ Error al cambiar el estado del día");
            setTimeout(() => {
                setMensaje("");
            }, 3000);
        }
    };

    useEffect(() => {
        obtenerEstadoDia();
    }, []);

    if (rol !== "jefe") return null;

    return (
        
        <div className="w-full max-w-xs bg-white dark:bg-gray-900 p-6 rounded-xl shadow-lg">
            <h2 className="text-2xl font-bold mb-4 text-center text-gray-800 dark:text-white">
                Gestión del Día
            </h2>

            <p className="text-center text-gray-700 dark:text-gray-300 mb-4">
                Estado actual:{" "}
                <span className={estadoDia === "abierto" ? "text-green-600" : "text-red-600"}>
                    {estadoDia}
                </span>
            </p>

            <div className="flex justify-center gap-4">
                <button
                    onClick={() => cambiarEstado("abrir")}
                    className="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded"
                >
                    Abrir Día
                </button>
                <button
                    onClick={() => cambiarEstado("cerrar")}
                    className="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded"
                >
                    Cerrar Día
                </button>
            </div>

          <AnimatePresence>
  {mensaje && (
    <motion.div
      key="toast"
      initial={{ opacity: 0, y: -20 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, y: -20 }}
      transition={{ duration: 0.6 }}
      className={`fixed top-5 right-5 z-50 px-5 py-4 rounded-xl shadow-lg max-w-sm
        ${estadoDia === "abierto"
          ? "bg-green-600 text-white"
          : estadoDia === "cerrado"
          ? "bg-red-600 text-white"
          : "bg-gray-600 text-white"
        }`}
    >
      <div className="font-semibold text-lg flex items-center gap-2">
        {estadoDia === "abierto" ? "☀️ Día Abierto" : estadoDia === "cerrado" ? "🌙 Día Cerrado" : "📅 Estado del Día"}
      </div>
      <p className="text-sm">{mensaje}</p>
    </motion.div>
  )}
</AnimatePresence>
        </div>
    );

}
