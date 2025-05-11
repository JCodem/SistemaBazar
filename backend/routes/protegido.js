import { Router } from 'express';
import { verificarToken, verificarRol } from '../middleware/auth.js';

const router = Router();

router.get('/solo-jefe', verificarToken, verificarRol('jefe'), (req, res) => {
  res.send(`Bienvenido jefe ${req.user.nombre}`);
});

router.get('/solo-vendedor', verificarToken, verificarRol('vendedor'), (req, res) => {
  res.send(`Hola vendedor ${req.user.nombre}`);
});

export default router;
