# Registration
When a user registers a business, he should be sent an email for verification, then we mark the business as active. In fact, there should a Mail 
directory under App to handle that, then each template directed to its own service where business logic lives
Templates to include are BusinessVerification, LowStockAlertMail, SubscriptionExpiryMail, MonthlyPerformanceMail, etc
This structure keeps the working directory clean and readable at scale
This also works well with the current system design

---

Status: implemented. The registration verification flow is live — the owner is sent a
`BusinessVerification` email and landed on `/verify-email`; see `account.md` for the
design and `launch-list.md` §2 for the email-delivery context. Open follow-ups (reminder
cadence, Resend domain verification, password-reset parity) are tracked in `account.md`
§10.
