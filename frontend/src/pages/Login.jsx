import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { login } from '../services/authService';

function Login() {
  const [correo, setCorreo] = useState('');
  const [contraseña, setContraseña] = useState('');
  const [error, setError] = useState('');
  const navigate = useNavigate();

  const handleLogin = async (e) => {
    e.preventDefault();
    try {
      const { token, rol } = await login(correo, contraseña);
      localStorage.setItem('token', token);
      localStorage.setItem('rol', rol);

      if (rol === 'jefe') navigate('/jefe');
      else if (rol === 'vendedor') navigate('/vendedor');
    } catch (err) {
      setError('Credenciales inválidas');
    }
  };

  return (
    <form onSubmit={handleLogin}>
      <h2>Iniciar Sesión</h2>
      <input type="email" value={correo} onChange={e => setCorreo(e.target.value)} placeholder="Correo" required />
      <input type="password" value={contraseña} onChange={e => setContraseña(e.target.value)} placeholder="Contraseña" required />
      <button type="submit">Ingresar</button>
      {error && <p style={{ color: 'red' }}>{error}</p>}
    </form>
  );
}

export default Login;
