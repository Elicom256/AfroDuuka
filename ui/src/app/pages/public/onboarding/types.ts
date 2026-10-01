export interface AccountData {
  firstname: string;
  lastname: string;
  email: string;
  phone: string;
  password: string;
  confirmPassword: string;
  username: string; // generated as @firstname
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
  password: '',
  confirmPassword: '',
  username: '', // will be generated as @firstname
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