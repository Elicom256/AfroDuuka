export interface AccountData {
  firstname: string;
  lastname: string;
  email: string;
  phone: string;
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

export interface OnboardingDraft {
  account: AccountData;
  business: BusinessData;
  branches: BranchData[];
  /**
   * True once the server has the account.
   *
   * Set after a successful signup so a retry after a later failure does not try to
   * create the same email again — that attempt comes back 422 and the owner is stuck
   * on a page whose only button is the one that fails.
   */
  accountCreated: boolean;
}

export const initialAccountData: AccountData = {
  firstname: '',
  lastname: '',
  email: '',
  phone: '',
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

/**
 * A business always starts with one branch. "Main Branch" is pre-filled because that is
 * what the column defaults to, so the owner can accept it rather than invent a name.
 */
export const initialBranchData: BranchData = {
  name: 'Main Branch',
  address: '',
  phone: '',
};

export const initialDraft: OnboardingDraft = {
  account: initialAccountData,
  business: initialBusinessData,
  branches: [initialBranchData],
  accountCreated: false,
};

export type StepErrors = Record<string, string>;

const EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const PHONE = /^\+?[\d\s()-]{7,20}$/;

/**
 * Validate one step in isolation.
 *
 * Each step validates itself rather than the whole draft being validated on every
 * keystroke: the owner is told about the business category while they are still on the
 * business step, not after they have filled in branches they could not have known they
 * needed. A step returns the field errors keyed by field name, and an empty object
 * means it may be left.
 */
export const validateStep = (
  step: 'account' | 'business' | 'branches',
  draft: OnboardingDraft,
): StepErrors => {
  const errors: StepErrors = {};

  if (step === 'account') {
    const { firstname, lastname, email, phone, password, confirmPassword } = draft.account;

    if (firstname.trim().length < 2) errors.firstname = 'Enter your first name.';
    if (lastname.trim().length < 2) errors.lastname = 'Enter your last name.';
    if (!EMAIL.test(email.trim())) errors.email = 'Enter a valid email address.';
    if (!PHONE.test(phone.trim())) errors.phone = 'Enter a valid phone number.';
    if (password.length < 6) errors.password = 'Use at least 6 characters.';
    if (password !== confirmPassword) errors.confirmPassword = 'Passwords do not match.';

    return errors;
  }

  if (step === 'business') {
    const { name, business_category_id, country_id, email, phone, address } = draft.business;

    if (name.trim().length < 2) errors.name = 'Enter your business name.';
    if (!business_category_id) errors.business_category_id = 'Choose a business category.';
    if (!country_id) errors.country_id = 'Choose your country.';
    // Both optional server-side, but if one is typed it has to be usable: the business
    // phone is the delivery address the welcome notification is sent to.
    if (email.trim() && !EMAIL.test(email.trim())) errors.email = 'Enter a valid email address.';
    if (phone.trim() && !PHONE.test(phone.trim())) errors.phone = 'Enter a valid phone number.';
    if (address.trim().length < 3) errors.address = 'Enter your business address.';

    return errors;
  }

  const seen = new Set<string>();

  draft.branches.forEach((branch, index) => {
    const label = branch.name.trim();

    if (label.length < 2) {
      errors[`branches.${index}.name`] = 'Enter a branch name.';
    } else if (seen.has(label.toLowerCase())) {
      // The column is unique on (business_id, name), so this is the one the server
      // cannot recover from on its own.
      errors[`branches.${index}.name`] = 'Branch names must be different.';
    } else {
      seen.add(label.toLowerCase());
    }

    if (branch.address.trim().length < 3) {
      errors[`branches.${index}.address`] = 'Enter the branch address.';
    }

    if (branch.phone.trim() && !PHONE.test(branch.phone.trim())) {
      errors[`branches.${index}.phone`] = 'Enter a valid phone number.';
    }
  });

  return errors;
};