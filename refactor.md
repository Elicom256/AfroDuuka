# REFACTOR

## Week 1: Fix P0 Broken Modules

**1. Wire up `Promotion` controller + routes**
This should portray a modern system
**2. Wire up `Coupon` controller + routes**
Generate coupon codes, the code should follow a specific cretaria
->First 4 letters od the business name, + the length of coupons a business has offered so far 3. Remove `AdminInventoryPage` end to end because I don't need it at all 6. delete todo.php route and delete `api.php` because todos and handled under user.php route 7. Delete History model migrations, UI everything

**8. Attachments**
Here, you'll just edit the products model and migration and add imoji, so that when the user is creating a product, he selects an imoji to
Wire this up both on the front end and back end
So, I don't wanna add images yet
On Product seeder, add emogis for products to

**9. Orders**
Wire up orders from back end to front end

## Constraints

- Edit migrations directly where needed
- Put comments on your work
- Do not hallucinate
- Maintain scalability
- Follow modern Laravel conventions and the current design system
- produce clean work
