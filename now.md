# Offline Gap
The system is claimed to have offline capabilities, but here are the gaps when offline:
- On pos page, when the user tries to search for a product, he can't access
- When the user tries to access sales page, he sees a blank page, but earlier, he was not able to create a sale because he was getting a toast
 error, I think the reason is beacuse some entries are nullable and were being ignored, resolve that to. Purchases
Resolve all issues like these on routes that should accept CRUD by the user
