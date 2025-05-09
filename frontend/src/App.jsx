import { BrowserRouter, Routes, Route } from 'react-router-dom';
import Login from './pages/Login';
import DashboardJefe from './pages/PanelJefe';
import DashboardVendedor from './pages/PanelVendedor';
import DashboardLayout from "./layouts/DashboardLayout";


function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<Login />} />
        <Route path="/jefe" element={<DashboardJefe />} />
        <Route path="/vendedor" element={<DashboardVendedor />} />
         <Route path="/" element={<Login />} />
        <Route path="/dashboard" element={<DashboardLayout />} />

      </Routes>
    </BrowserRouter>
  );
}

export default App;
