# Today's work at DuukaFlow

## Task: Add the following Models

## CRUD Part
**Record tax payments:**
Here, we're dealing with 2 different ut related things at a go. ie tax payments and taxes obligated by the business (types of taxes paid by the business)
They both reside under AdminTaxesPage componet and they're self explanatory
So these models should all have CRUD functionality

**Admin Finances Page**
render a clean UI/UX of the finances page on the admin dashboard, it's in the AdminFinancesPage component
Re-use periods on calling the cashflow api as used in CashFlowAnalytics
So the cashflow should really show how the business finances are flowing
So generally, this' an advanced/detailed version of cashflow analytics

**Employee Remunerartion**
This' under the AdminEmployeeRemunerationPage component. It still needs some part of CRUD, the needed features of crud are create, read, update. on the update, it's just to deal with the status. 
the status at the backend expects an enum [pending,paid,failed]
Example of data expected at the backend
{
      "worker_id": 1,
      "amount": 1200000.00,
      "type": "salary",
      "payment_date": "2026-06-01",
      "reference": "SAL-JUN-2026-001", ==>this can be null
      "status": "paid",
      "description": "Monthly salary payment for June 2026"
    }
## Constraints
✅ Do not hallucinate features
✅ Execute the work like a senior dev
✅ Where show data by id is needed and there's no component for it, create it like a pro
✅ Ensure no errors and typos
✅ Don't re-invent the wheel on styling components, use shadcn
✅ Ensure cute UI/UX