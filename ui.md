# Front end work
## Problem
The UI has been having a special error, when docker dev is started eg (dcdev up -d / dcdev restart), every route outside the dashboard directs
the user to the login page (http://localhost/login) eg, when user taps Home/Pricing/Docs/About/Start Onboarding, both take him to the login. Beyond
this, the login page appears empty, no login form. And also, no login route besides the start onboarding as it was before.
##Task
You're a senior full stack and front end focused dev, identify the problem and look for the possible permanent solution, then implement it
## Things to know
I guess this might be an auth token issue, and this project is decoupled, so the issue might be how I'm handling tokens. But this is just my view,
look for the real issue and permanently fix it

## Constraints
- Do not hallucinate
- Preserve the current system design

