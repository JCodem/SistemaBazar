const express = require('express');
const { verificarToken, verificarRol } = require('../middleware/auth');
const router = express.Router();

router.get('/solo-jefe', verificarToken, verificarRol('jefe'), (req, res) => {
  res.send(`Bienvenido jefe ${req.user.nombre}`);
});

router.get('/solo-vendedor', verificarToken, verificarRol('vendedor'), (req, res) => {
  res.send(`Hola vendedor ${req.user.nombre}`);
});

module.exports = router;
