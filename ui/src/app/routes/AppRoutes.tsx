import { Route, Routes } from 'react-router-dom';
import { Home } from '../pages/public/Home';
import { About } from '../pages/public/About';
import { Documentation } from '../pages/public/Documentation';

export const AppRoutes = () => {
  return (
    <div>
      <Routes>
        <Route path='/' element={<Home />} />
        <Route path='about' element={<About />} />
        <Route path='documentation' element={<Documentation />} />
      </Routes>
    </div>
  );
};
