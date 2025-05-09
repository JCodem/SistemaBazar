import { Sequelize } from "sequelize";

const sequelize = new Sequelize("bazar", "root", "", {
  host: "localhost",
  dialect: "mysql"
});

export default sequelize;
