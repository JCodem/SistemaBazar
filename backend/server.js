import db from "./models/index.js";
import dotenv from "dotenv";
import app from "./app.js";

db.sequelize.sync();
dotenv.config();



const PORT = process.env.PORT || 5000;
app.listen(PORT, () => {
  console.log(`Server is running on port ${PORT}`);
});

export default app;
