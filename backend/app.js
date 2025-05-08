const express = require('express');
const cors = require('cors');
const sequelize = require('./config/database');
const authRoutes = require('./routes/auth');
const rutasProtegidas = require('./routes/protegido');

const app = express();
app.use(cors());
app.use(express.json());   
app.use('/api/auth', authRoutes);
app.use('/api/protegido', rutasProtegidas);

//aqui iran las rutas de los endpoints
app.get('/', (req, res) => {
    res.send('Api Funcionando , LETSGOOOOO !');
});

sequelize.sync({ alter: true })
  .then(() => console.log('Base de datos sincronizada'))
  .catch(err => console.error('Error al sincronizar DB:', err));


module.exports = app;
