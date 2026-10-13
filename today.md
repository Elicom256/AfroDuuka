# Registration
When a user registers a business, he should be sent an email for verification, then we mark the business as active. In fact, there should a Mail 
directory under App to handle that, then each template directed to its own service where business logic lives
Templates to include are BusinessVerification, LowStockAlertMail, SubscriptionExpiryMail, MonthlyPerformanceMail, etc
This structure keeps the working directory clean and readable at scale
This also works well with the current system design
