# Refactor

## subscriptions

- Here, pass only plan_id from the front end, then others should be handled at the backend

## Subscription

- Here, pass [subscription_id, payment_method_id, amount_paid, transaction_id, number_paid]. Then others will be picked up from the backend, and others keep nullable

### Constraints

- Do not hallucinate
- Check the possible outcome before you code
- Implement scalable approaches
- Include comments in your work
- Ensure the end points are matched well with the backend routes
