# MODULE 4 — Price History

Track every product price change.

## Backend

Create:

- PriceHistory
- Fields:
- business_branch_product_id
- old_cost_price
- new_cost_price
- old_sale_price
- new_sale_price
- changed_by
- change_reason (optional)
- timestamps

Automatically record history whenever prices change.
Use observers/events.
Never overwrite historical records.
Expose API:
Product price timeline
Latest changes
Filter by product
Filter by date

## Frontend

**On Product Details add:**

- Price History tab
- Timeline
- Table showing:
- Date
- Old Cost
- New Cost
- Old Selling
- New Selling
- User
- Reason
- Include pagination.

### Constraints
- Do not hallucinate
- Check the possible outcome before you code
- Implement scalable approaches
- Include comments in your work
- Ensure the end points are matched well with the backend routes
