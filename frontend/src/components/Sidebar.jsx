
export default function Sidebar({ rol }) {
    const cerrarSesion = () => {
        localStorage.clear();
        window.location.href = "/";
    };


    const toggleDarkMode = () => {
        document.documentElement.classList.toggle('dark');
    };

    function SidebarItem({ icon, text }) {
        return (
            <button className="flex items-center gap-2 w-full px-3 py-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                <span>{icon}</span>
                <span>{text}</span>
            </button>
        );
    }

    return (
        <aside className="w-64 bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-100 shadow-lg h-screen p-4 flex flex-col justify-between">
            <div>
                <h2 className="text-xl font-bold text-blue-600 dark:text-blue-400 mb-6 text-center">
                    Panel {rol}
                </h2>

                <nav className="space-y-2">
                    {rol === "jefe" && (
                        <>
                            <SidebarItem icon="📦" text="Productos" />
                            <SidebarItem icon="📊" text="Reportes" />
                            <SidebarItem icon="🕒" text="Apertura/Cierre" />
                        </>
                    )}

                    {rol === "vendedor" && (
                        <>
                            <SidebarItem icon="🧾" text="Generar Boleta" />
                            <SidebarItem icon="🛒" text="Nueva Venta" />
                        </>
                    )}
                </nav>
            </div>

            <div className="space-y-2">
                <button
                    onClick={toggleDarkMode}
                    className="w-full text-left text-sm hover:text-blue-600 transition-colors"
                >
                    🌓 Cambiar modo oscuro
                </button>

                <button
                    onClick={cerrarSesion}
                    className="w-full text-left text-red-500 hover:underline"
                >
                    🔓 Cerrar sesión
                </button>
            </div>
        </aside>
    );
}
