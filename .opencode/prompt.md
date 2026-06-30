# Some cleanup and AI API integration

## Task 1: Expiry date on Business Branch Products
- Add expiry date on business_branch_products. This should be nullable and add it to where these business branch products are being seeded
- Add AI tool tool that handles this such that when a user asks about tools about to expire, or expired, or danger zone of these products that they are about to expire
- At the front end, add a badge with shadcn notifying of the number of products about to expire

## Task 2: Additional work
 **(a) Smart Restocking Prediction**
- Add a service to scan this and call it at the front end. It should be called at the landing page of the dashboard, so it doesn't need an independent front end route. It should be just below the AI UI and the other div that's in flex with the AI.
- Use shadcn here, so no re-inventing the wheel and you should preserve nice UI/UX
 **(b) Todo List**
- Add a todo list for the user, this should be in flex with  the **Smart Restocking Prediction**

## Task 3: Agent Completion
- Complete the AI agent to perform its work. This includes both UI and API. The GEMINI_API_KEY is in env and 
google-gemini-php/client package has already been installed
> I expect to be having an interactive agent at the end of the task

**Constraints:**
- Add comments to your work for clarity
- Follow modern best practices and the design pattern of the application
- Consider scalability of the application

