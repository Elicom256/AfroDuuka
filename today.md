# Front end Notification:id routing
## Issue
Notificationhttp routing is tight, but but assuming a user is on ://localhost/dashboard/notifications/14 and he tries to access/open the related
module, forexample if the notification is about sales, on http://localhost/dashboard/notifications/14, the user tries to access the related sale,
he hits the 404 page yet he should be routed to the related sale:id properly
This error cuts across all notification:id whether related to sales, purchases, etc
## Task
Find out why the user is taken to the 404 page and fix it

