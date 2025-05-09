import express from "express";
import db from "../models/index.js";

const { Producto } = db;
const { Op } = db.Sequelize;

const router = express.Router();

// Crear producto
router.post("/", async (req, res) => {
  try {
    const nuevo = await Producto.create(req.body);
    res.status(201).json(nuevo);
  } catch (err) {
    res.status(400).json({ error: err.message });
  }
});

// Listar productos
router.get("/", async (req, res) => {
  const productos = await Producto.findAll();
  res.json(productos);
});

router.get("/buscar", async (req, res) => {
  const { q } = req.query;
  const productos = await Producto.findAll({
    where: {
      [Op.or]: [
        { nombre: { [Op.like]: `%${q}%` } },
        { codigo: { [Op.like]: `%${q}%` } },
      ]
    }
  });
  res.json(productos);
});

// Buscar por ID
router.get("/:id", async (req, res) => {
  const producto = await Producto.findByPk(req.params.id);
  if (producto) res.json(producto);
  else res.status(404).json({ error: "No encontrado" });
});

// Actualizar
router.put("/:id", async (req, res) => {
  try {
    const [filas] = await Producto.update(req.body, {
      where: { id: req.params.id },
    });
    if (filas > 0) res.json({ mensaje: "Actualizado" });
    else res.status(404).json({ error: "No encontrado" });
  } catch (err) {
    res.status(400).json({ error: err.message });
  }
});

// Eliminar
router.delete("/:id", async (req, res) => {
  const filas = await Producto.destroy({ where: { id: req.params.id } });
  if (filas > 0) res.json({ mensaje: "Eliminado" });
  else res.status(404).json({ error: "No encontrado" });
});

export default router;
