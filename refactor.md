# POS

Read the codebase and understand sales vs pos
On POS, we're actually dealing with sales in an easy way for users, just as modern inventory systems do.

## Task

pos has cart. Remove the cart model and everything, just create a sale instead. So where you see the cart for the front end, actually create a sale, and scan/search products and add them to a sale as sale items. In short, check what we're doing in the sales route and apply the same to pos, just the difference is that the one under pos is for easing cashier/admin/managers work, because it feels like a real world pos

## Constraints

- Do not hallucinate
- Do a clean work
- Make sure you remove the models that relate to cart
- Use the sale status to handle held sales etc, typically to handle what cart was doing
