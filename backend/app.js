import express from "express";
import cors from "cors";
import authRoutes from "./routes/auth.js";
import sequelize from "./config/database.js";
import rutasProtegidas from "./routes/protegido.js";
import productosRouter from "./routes/productos.js";
import diaRoutes from './routes/dia.js';

const app = express();

app.use(cors());
app.use(express.json());


app.use("/api/protegido", rutasProtegidas);
app.use("/api/productos", productosRouter);
app.use("/api/auth", authRoutes);
app.use('/api/dia', diaRoutes);
// Ruta raíz de prueba
app.get("/", (req, res) => {
  res.send("¡API funcionando, LETSGOOOOO!");
});

// Conexión y sincronización con la base de datos
sequelize.sync({ alter: true })
  .then(() => console.log("Base de datos sincronizada"))
  .catch(err => console.error("Error al sincronizar DB:", err));

// ✅ Exportar app como default (ES Modules)
export default app;
