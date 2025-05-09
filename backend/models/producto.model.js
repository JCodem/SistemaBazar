export default (sequelize, DataTypes) => {
  const Producto = sequelize.define("Producto", {
    nombre: {
      type: DataTypes.STRING,
      allowNull: false,
    },
    codigo: {
      type: DataTypes.STRING,
      allowNull: false,
      unique: true,
    },
    valor: {
      type: DataTypes.INTEGER,
      allowNull: false,
    },
    descripcion: {
      type: DataTypes.TEXT,
    },
  });

  return Producto;
};
