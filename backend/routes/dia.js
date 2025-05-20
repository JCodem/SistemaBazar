import express from 'express';
import Dia from '../models/dia.js';
const router = express.Router();

// Abrir el día
router.post('/abrir', async (req, res) => {
  const hoy = new Date().toISOString().split('T')[0];

  try {
    let dia = await Dia.findOne({ where: { fecha: hoy } });

    if (dia) {
      dia.estado = 'abierto';
      await dia.save();
    } else {
      dia = await Dia.create({ fecha: hoy, estado: 'abierto' });
    }

    res.json({ mensaje: 'Día abierto correctamente', dia });
  } catch (error) {
    res.status(500).json({ error: 'Error al abrir el día' });
  }
});

// Cerrar el día
router.post('/cerrar', async (req, res) => {
  const hoy = new Date().toISOString().split('T')[0];

  try {
    let dia = await Dia.findOne({ where: { fecha: hoy } });

    if (dia) {
      dia.estado = 'cerrado';
      await dia.save();
    } else {
      dia = await Dia.create({ fecha: hoy, estado: 'cerrado' });
    }

    res.json({ mensaje: 'Día cerrado correctamente', dia });
  } catch (error) {
    res.status(500).json({ error: 'Error al cerrar el día' });
  }
});

// Obtener estado actual del día
router.get('/hoy', async (req, res) => {
  const hoy = new Date().toISOString().split('T')[0];

  try {
    const dia = await Dia.findOne({ where: { fecha: hoy } });

    if (dia) {
      res.json(dia);
    } else {
      res.json({ fecha: hoy, estado: 'cerrado' }); // por defecto
    }
  } catch (error) {
    res.status(500).json({ error: 'Error al obtener estado del día' });
  }
});

export default router;
