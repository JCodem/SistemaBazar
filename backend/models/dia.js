import { DataTypes } from 'sequelize';
import sequelize from '../config/database.js';

const Dia = sequelize.define('Dia', {
  fecha: {
    type: DataTypes.DATEONLY,
    allowNull: false,
    unique: true
  },
  estado: {
    type: DataTypes.ENUM('abierto', 'cerrado'),
    allowNull: false
  }
});

export default Dia;
