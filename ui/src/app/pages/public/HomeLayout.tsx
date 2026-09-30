import { Outlet } from 'react-router-dom';
import { useLocation } from 'react-router-dom';

export const HomeLayout = () => {
  const isHome = useLocation().pathname === '/';

  return (
    <div className={isHome ? 'marketing-home flex-1 px-4' : 'container mx-auto px-4 py-10 sm:py-14'}>
      <Outlet />
    </div>
  );
};
