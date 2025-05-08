import axios from 'axios';

export const login = async (correo, contraseña) => {
  const response = await axios.post('http://localhost:5000/api/auth/login', {
    correo,
    contraseña,
  });
  return response.data;
};
