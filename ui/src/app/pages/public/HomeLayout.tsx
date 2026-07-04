import { Outlet } from 'react-router-dom';

export const HomeLayout = () => {
  return (
    <div className='container mx-auto px-4 py-10 sm:py-14'>
      <Outlet />
    </div>
  );
};
