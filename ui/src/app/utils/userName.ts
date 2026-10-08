/**
 * A user's display name.
 *
 * There is no `name` column on the users table. A name is a `firstname` and a
 * `lastname`, so anything reaching for `user.name` gets undefined and renders blank or
 * a dash. Two separate audit pages did exactly that, which is why an auditor's name was
 * missing on a record that had one.
 *
 * Either half may be null, so a user who has filled in only one still renders
 * something rather than an empty string.
 */
export type NamedUser = {
  firstname?: string | null;
  lastname?: string | null;
};

export const personName = (person?: NamedUser | null): string =>
  [person?.firstname, person?.lastname]
    .map((part) => (part ?? '').trim())
    .filter(Boolean)
    .join(' ');
