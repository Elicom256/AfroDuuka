import { Route, Routes } from 'react-router-dom';
import { Home } from '../pages/public/Home';
import { About } from '../pages/public/About';
import { Documentation } from '../pages/public/Documentation';
import { Login } from '../pages/public/Login';
import { SignUp } from '../pages/public/SignUp';
import { AdminRoutes } from './AdminRoutes';
import { useLoggedinUserQuery } from '../store/features/auth/authQuery';
import { ManagerRoutes } from './ManagerRoutes';
import { StaffDashboard } from './StaffDashboard';
import { NotFound } from './NotFound';

export const AppRoutes = () => {
  const { data } = useLoggedinUserQuery();
  const role = data?.data.role.name;

  return (
    <Routes>
      {/* Public routes */}
      <Route path='/' element={<Home />} />
      <Route path='login' element={<Login />} />
      <Route path='signup' element={<SignUp />} />
      <Route path='about' element={<About />} />
      <Route path='documentation' element={<Documentation />} />

      {/* Role-based protected routes */}
      {role === 'admin' && <Route path='/*' element={<AdminRoutes />} />}
      {role === 'manager' && <Route path='/*' element={<ManagerRoutes />} />}
      {role === 'staff' && <Route path='/*' element={<StaffDashboard />} />}

      {/* Fallback */}
      <Route path='*' element={<NotFound />} />
    </Routes>
  );
};
