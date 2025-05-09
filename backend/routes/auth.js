import express from "express";
import bcrypt from "bcryptjs";
import jwt from "jsonwebtoken";
import db from "../models/index.js";

const router = express.Router();
const { User } = db;

// Registro
router.post("/register", async (req, res) => {
  const { nombre, correo, contraseña, rol } = req.body;

  try {
    const existe = await User.findOne({ where: { correo } });
    if (existe) return res.status(400).json({ error: "Correo ya registrado" });

    const hash = await bcrypt.hash(contraseña, 10);
    const nuevoUsuario = await User.create({ nombre, correo, contraseña: hash, rol });

    res.status(201).json({ mensaje: "Usuario registrado con éxito", usuario: nuevoUsuario });
  } catch (err) {
    res.status(500).json({ error: "Error al registrar usuario", detalle: err.message });
  }
});

// Login
router.post("/login", async (req, res) => {
  const { correo, contraseña } = req.body;

  try {
    const usuario = await User.findOne({ where: { correo } });
    if (!usuario) return res.status(404).json({ error: "Usuario no encontrado" });

    const esValida = await bcrypt.compare(contraseña, usuario.contraseña);
    if (!esValida) return res.status(401).json({ error: "Contraseña incorrecta" });

    const token = jwt.sign(
      { id: usuario.id, rol: usuario.rol, nombre: usuario.nombre },
      process.env.JWT_SECRET,
      { expiresIn: "1d" }
    );

    res.json({ token, rol: usuario.rol, nombre: usuario.nombre });
  } catch (err) {
    res.status(500).json({ error: "Error al iniciar sesión", detalle: err.message });
  }
});

export default router;
