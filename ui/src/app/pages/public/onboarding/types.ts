export interface AccountData {
  firstname: string;
  lastname: string;
  email: string;
  phone: string;
  country_id: string;
  password: string;
  confirmPassword: string;
}

export interface BusinessData {
  name: string;
  business_category_id: string;
  country_id: string;
  email: string;
  phone: string;
  address: string;
}

export interface BranchData {
  name: string;
  address: string;
  phone: string;
}

export const initialAccountData: AccountData = {
  firstname: '',
  lastname: '',
  email: '',
  phone: '',
  country_id: '',
  password: '',
  confirmPassword: '',
};

export const initialBusinessData: BusinessData = {
  name: '',
  business_category_id: '',
  country_id: '',
  email: '',
  phone: '',
  address: '',
};

export const initialBranchData: BranchData = {
  name: '',
  address: '',
  phone: '',
};
