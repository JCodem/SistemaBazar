const jwt = require('jsonwebtoken');

function verificarToken(req, res, next) {
  const token = req.headers['authorization'];

  if (!token) {
    return res.status(401).json({ error: 'Token no proporcionado' });
  }

  try {
    const decoded = jwt.verify(token, process.env.JWT_SECRET);
    req.user = decoded; // Guarda los datos del usuario en la request
    next();
  } catch (err) {
    return res.status(401).json({ error: 'Token inválido' });
  }
}

function verificarRol(rolRequerido) {
  return (req, res, next) => {
    if (req.user.rol !== rolRequerido) {
      return res.status(403).json({ error: 'Acceso denegado: rol insuficiente' });
    }
    next();
  };
}

module.exports = { verificarToken, verificarRol };
