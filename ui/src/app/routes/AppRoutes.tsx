import { Route, Routes } from 'react-router-dom';
import { Home } from '../pages/public/Home';
import { About } from '../pages/public/About';
import { Documentation } from '../pages/public/Documentation';
import { Login } from '../pages/public/Login';
import { SignUp } from '../pages/public/SignUp';

export const AppRoutes = () => {
  return (
    <div>
      <Routes>
        <Route path='/' element={<Home />} />
        <Route path='login' element={<Login />} />
        <Route path='signup' element={<SignUp />} />
        <Route path='about' element={<About />} />
        <Route path='documentation' element={<Documentation />} />
      </Routes>
    </div>
  );
};
