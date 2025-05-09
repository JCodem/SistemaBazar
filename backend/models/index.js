import Sequelize from "sequelize";
import dbConfig from "../config/db.config.js";
import UserModel from "./user.js";
import ProductoModel from "./producto.model.js";

const sequelize = new Sequelize(dbConfig.DB, dbConfig.USER, dbConfig.PASSWORD, {
  host: dbConfig.HOST,
  dialect: dbConfig.dialect,
});

const db = {};
db.Sequelize = Sequelize;
db.sequelize = sequelize;
db.User = UserModel(sequelize, Sequelize.DataTypes);
db.Producto = ProductoModel(sequelize, Sequelize.DataTypes);

// Exporta correctamente para ES Modules
export default db;
