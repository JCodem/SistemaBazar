export default (sequelize, DataTypes) => {
  return sequelize.define("User", {
    nombre: {
      type: DataTypes.STRING,
      allowNull: false
    },
    correo: {
      type: DataTypes.STRING,
      unique: true,
      allowNull: false
    },
    contraseña: {
      type: DataTypes.STRING,
      allowNull: false
    },
    rol: {
      type: DataTypes.STRING,
      allowNull: false
    }
  });
};
