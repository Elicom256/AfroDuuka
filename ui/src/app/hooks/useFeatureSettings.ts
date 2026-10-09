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

    // Every settings endpoint answers `{ settings, message }`; reading `data.data`
    // missed the payload entirely, so every gated sidebar entry (suppliers,
    // customers, reports, promotions, attendance) stayed hidden no matter the status.
    const payload = data.settings ?? data.data ?? data;

    if (Array.isArray(payload)) return payload.some((item: any) => item?.status === 'enabled');
    return payload?.status === 'enabled';
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
