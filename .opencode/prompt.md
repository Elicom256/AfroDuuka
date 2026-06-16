# DuukaFlow REFACTOR

According to the suggestion made in addons.md, follow the current system design and add those models suggested

- You can edit migrations because I have no critical data in the db (ie I havent deployed)
- Take mature decison (like making non critical inputs nullable eg QRcode)
- On the Business Branch, add Currecy Used because A business may have a branch in another country. This is to make the front end clean to avoid typing UGX everywhere and consistency in accounting. For other currecies, look for a scalable way to convert it to UGX
- Keep routes separated for scalability and readability

## Front end

Add types folder to keep all the types
Make filters by period centralized for the app
Settings page is squezed, make it real world
Implement theme toggling inside settings

## What to leave for now

2. Offline-First & Sync Engine (PWA + IndexedDB) -> This will be implemented after getting some customers

3. Credit & Savings Groups (VSLA/ASCAs Integration)

## Constraints

- Add comments to the code
- Do not hallucinate features
- Keep the system scalable
- Document what you've done in done.md (Create this file)
- Keep User's roles separate
- Do NOT complicate things

**Note:** Work as a senior engineer
