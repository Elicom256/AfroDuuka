# To Check

- Verify onboarding end-to-end still logs the exact failing constraint if `SQLSTATE 23514` reappears
- Confirm `oct` changes are actually available on the deployed branch/running build, not just pushed to `origin/oct`
- Re-test the business category selector in the live UI; API returns seeded categories, but selector was still seen empty
- Re-test the new country popover selector on narrow mobile screens and with a long search query
- Confirm `/signup` no longer renders the old `SignUp.tsx` page and now lands on `/onboarding`
- Verify Resend email works beyond the free-tier test address after domain verification
