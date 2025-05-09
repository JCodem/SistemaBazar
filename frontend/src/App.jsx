import { BrowserRouter, Routes, Route } from 'react-router-dom';
import Login from './pages/Login';
import DashboardJefe from './pages/DashboardJefe';
import DashboardVendedor from './pages/DashboardVendedor';

function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<Login />} />
        <Route path="/jefe" element={<DashboardJefe />} />
        <Route path="/vendedor" element={<DashboardVendedor />} />

      </Routes>
    </BrowserRouter>
  );
}

export default App;
