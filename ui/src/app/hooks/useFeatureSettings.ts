import { useGetReportsSettingsQuery } from '@/app/store/features/business/settings/reports';
import { useGetCustomerSettingsQuery } from '@/app/store/features/business/settings/customer';
import { useGetSupplierSettingsQuery } from '@/app/store/features/business/settings/supplier';
import { useGetPromotionsSettingsQuery } from '@/app/store/features/business/settings/promotions';
import { useGetAttendanceSettingsQuery } from '@/app/store/features/business/settings/attendance';

export interface FeatureSettings {
  reports: boolean;
  customers: boolean;
  suppliers: boolean;
  promotions: boolean;
  attendance: boolean;
  loading: boolean;
}

export const useFeatureSettings = (): FeatureSettings => {
  const { data: reportsData, isLoading: reportsLoading } = useGetReportsSettingsQuery();
  const { data: customersData, isLoading: customersLoading } = useGetCustomerSettingsQuery();
  const { data: suppliersData, isLoading: suppliersLoading } = useGetSupplierSettingsQuery();
  const { data: promotionsData, isLoading: promotionsLoading } = useGetPromotionsSettingsQuery();
  const { data: attendanceData, isLoading: attendanceLoading } = useGetAttendanceSettingsQuery();

  const isEnabled = (data: any): boolean => {
    if (!data) return false;
    if (Array.isArray(data)) return data.some((item: any) => item.status === 'enabled');
    if (data.data) {
      if (Array.isArray(data.data)) return data.data.some((item: any) => item.status === 'enabled');
      return data.data?.status === 'enabled';
    }
    return data?.status === 'enabled';
  };

  return {
    reports: isEnabled(reportsData),
    customers: isEnabled(customersData),
    suppliers: isEnabled(suppliersData),
    promotions: isEnabled(promotionsData),
    attendance: isEnabled(attendanceData),
    loading: reportsLoading || customersLoading || suppliersLoading || promotionsLoading || attendanceLoading,
  };
};
